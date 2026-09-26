<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Db;

use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

/** Read-only adapter for Photos 6.x (Nextcloud 33). No direct PDO or hardcoded DB prefix. */
final class AlbumRepository {
    public function __construct(private IDBConnection $db) {}

    public function findPublic(array $owners): array {
        $q = $this->db->getQueryBuilder();
        $q->select('a.album_id', 'a.name', 'a.user', 'a.created', 'a.location')
            ->selectAlias('s.collaborator_id', 'token')
            ->from('photos_albums', 'a')
            ->innerJoin('a', 'photos_albums_collabs', 's', $q->expr()->eq('a.album_id', 's.album_id'))
            ->where($q->expr()->eq('s.collaborator_type', $q->createNamedParameter(3, IQueryBuilder::PARAM_INT)));
        if ($owners !== []) {
            $q->andWhere($q->expr()->in('a.user', $q->createNamedParameter($owners, IQueryBuilder::PARAM_STR_ARRAY)));
        }
        $result = $q->executeQuery();
        try {
            return $result->fetchAll();
        } finally {
            $result->closeCursor();
        }
    }

    /** Only source directories of already-authorized albums. Paths remain server-side. */
    public function getSourceFolders(array $ids): array {
        if ($ids === []) { return []; }
        $rows = [];
        // Keep below database parameter limits, including for larger installations.
        foreach (array_chunk($ids, 500) as $chunk) {
            $q = $this->db->getQueryBuilder();
            $q->select('p.album_id', 'd.path')
                ->from('photos_albums_files', 'p')
                ->innerJoin('p', 'filecache', 'f', $q->expr()->eq('p.file_id', 'f.fileid'))
                ->innerJoin('f', 'filecache', 'd', $q->expr()->eq('f.parent', 'd.fileid'))
                ->where($q->expr()->in('p.album_id', $q->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)))
                ->groupBy('p.album_id', 'd.path');
            $result = $q->executeQuery();
            try { array_push($rows, ...$result->fetchAll()); } finally { $result->closeCursor(); }
        }
        return $rows;
    }

    /** One query for the displayed page; ignores files absent from Nextcloud's file cache. */
    public function getMediaSummary(array $ids): array {
        if ($ids === []) {
            return [];
        }
        $q = $this->db->getQueryBuilder();
        $q->select('p.album_id')
            ->selectAlias($q->func()->count('f.fileid'), 'media_count')
            ->selectAlias($q->func()->max('f.fileid'), 'preview_id')
            ->from('photos_albums_files', 'p')
            ->innerJoin('p', 'filecache', 'f', $q->expr()->eq('p.file_id', 'f.fileid'))
            ->where($q->expr()->in('p.album_id', $q->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)))
            ->groupBy('p.album_id');
        $result = $q->executeQuery();
        try {
            $summaries = [];
            foreach ($result->fetchAll() as $row) {
                $summaries[(int)$row['album_id']] = [
                    'count' => (int)$row['media_count'], 'preview' => (int)$row['preview_id'],
                ];
            }
            return $summaries;
        } finally {
            $result->closeCursor();
        }
    }

    /** Photos chooses the last-added file as cover, with verified album membership. */
    public function getCovers(array $ids): array {
        if ($ids === []) { return []; }
        $q = $this->db->getQueryBuilder();
        $q->select('a.album_id', 'f.fileid')->from('photos_albums', 'a')
            ->innerJoin('a', 'filecache', 'f', $q->expr()->eq('a.last_added_photo', 'f.fileid'))
            ->innerJoin('f', 'photos_albums_files', 'p', $q->expr()->eq('f.fileid', 'p.file_id'))
            ->where($q->expr()->eq('p.album_id', 'a.album_id'))
            ->andWhere($q->expr()->in('a.album_id', $q->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));
        $r = $q->executeQuery();
        try { $out=[]; foreach ($r->fetchAll() as $row) { $out[(int)$row['album_id']] = (int)$row['fileid']; } return $out; }
        finally { $r->closeCursor(); }
    }

    public function getFilenameCovers(array $ids, string $needle): array {
        if($ids===[] || $needle===''){return [];}
        $q=$this->db->getQueryBuilder();
        $q->select('p.album_id','f.fileid','f.name')->from('photos_albums_files','p')
            ->innerJoin('p','filecache','f',$q->expr()->eq('p.file_id','f.fileid'))
            ->innerJoin('f','mimetypes','m',$q->expr()->eq('f.mimetype','m.id'))
            ->where($q->expr()->in('p.album_id',$q->createNamedParameter($ids,IQueryBuilder::PARAM_INT_ARRAY)))
            ->andWhere($q->expr()->like('m.mimetype',$q->createNamedParameter('image/%')));
        $result=$q->executeQuery();$choices=[];
        try{while($row=$result->fetch()){
            if(mb_stripos($row['name'],$needle,0,'UTF-8')===false){continue;}
            $id=(int)$row['album_id'];$key=mb_strtolower($row['name'],'UTF-8');
            if(!isset($choices[$id]) || strcmp($key,$choices[$id]['key'])<0
                || ($key===$choices[$id]['key'] && (int)$row['fileid']<$choices[$id]['id'])){
                $choices[$id]=['key'=>$key,'id'=>(int)$row['fileid']];
            }
        }}finally{$result->closeCursor();}
        return array_map(static fn($c)=>$c['id'],$choices);
    }

    public function getMediaTypes(array $ids): array {
        $ids=array_values(array_unique(array_filter(array_map('intval',$ids))));
        if ($ids===[]) { return []; }
        $q=$this->db->getQueryBuilder();
        $q->select('f.fileid','m.mimetype')->from('filecache','f')
            ->innerJoin('f','mimetypes','m',$q->expr()->eq('f.mimetype','m.id'))
            ->where($q->expr()->in('f.fileid',$q->createNamedParameter($ids,IQueryBuilder::PARAM_INT_ARRAY)));
        $r=$q->executeQuery();
        try { $out=[];foreach($r->fetchAll() as $row){$out[(int)$row['fileid']]=$row['mimetype'];}return $out; }
        finally { $r->closeCursor(); }
    }

    /** Membership and file owner, never a public filesystem path. */
    public function findFile(int $albumId, int $fileId): ?array {
        $q = $this->db->getQueryBuilder();
        $q->select('p.owner', 'p.file_id')->from('photos_albums_files', 'p')
            ->where($q->expr()->eq('p.album_id', $q->createNamedParameter($albumId, IQueryBuilder::PARAM_INT)))
            ->andWhere($q->expr()->eq('p.file_id', $q->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));
        $r = $q->executeQuery();
        try { return $r->fetch() ?: null; } finally { $r->closeCursor(); }
    }

    /** Stable cursor pagination, image and video media. Each media request independently checks access. */
    public function getPhotos(int $albumId, int $after, int $limit): array {
        $q = $this->db->getQueryBuilder();
        $q->select('p.album_file_id', 'f.fileid', 'f.name', 'm.mimetype')
            ->from('photos_albums_files', 'p')
            ->innerJoin('p', 'filecache', 'f', $q->expr()->eq('p.file_id', 'f.fileid'))
            ->innerJoin('f', 'mimetypes', 'm', $q->expr()->eq('f.mimetype', 'm.id'))
            ->where($q->expr()->eq('p.album_id', $q->createNamedParameter($albumId, IQueryBuilder::PARAM_INT)))
            ->andWhere($q->expr()->gt('p.album_file_id', $q->createNamedParameter($after, IQueryBuilder::PARAM_INT)))
            ->andWhere($q->expr()->orX($q->expr()->like('m.mimetype', $q->createNamedParameter('image/%')), $q->expr()->like('m.mimetype', $q->createNamedParameter('video/%'))))
            ->orderBy('p.album_file_id', 'ASC')->setMaxResults($limit);
        $result = $q->executeQuery();
        try { return $result->fetchAll(); } finally { $result->closeCursor(); }
    }
}
