# Security policy

We welcome good-faith security research. Our approach follows the principles of [Coordinated Vulnerability Disclosure (CVD) described by CERT/CC](https://certcc.github.io/CERT-Guide-to-CVD/). The testing and researcher-protection terms below are adapted from the [disclose.io vulnerability disclosure policy](https://disclose.io/framework/terms/vdp/) (CC0-1.0).

## Scope and safe testing

This policy covers the Photo Gallery code maintained in this repository. Test on your own installation or one for which you have explicit permission from its operator. The linked production gallery is an example, not a testing target; this policy does not authorize testing Ikigaido's live services or third-party installations.

- Use test accounts and data you control. Avoid disrupting services, changing or destroying others' data, or accessing personal information.
- Limit exploitation to the minimum needed to demonstrate the issue. If you encounter someone else's data, stop testing and report the issue without copying or sharing that data.
- Ask through the private reporting channel if you are unsure whether an activity is covered.

## Reporting a vulnerability

Please use [GitHub's private vulnerability reporting form](https://github.com/ikigaido-org/nextcloud-photo-gallery/security/advisories/new). A GitHub account is required.

Report suspected vulnerabilities promptly and privately rather than posting details in a public issue or pull request. Include, where possible:

- The affected Photo Gallery and Nextcloud versions.
- Steps to reproduce the issue, or a minimal proof of concept.
- The potential impact and relevant configuration.

Remove passwords, access tokens and personal data from examples and logs. If possible, check whether the latest release is affected, but do not delay a report to do so.

## Coordination and disclosure

We will acknowledge reports, investigate their impact and keep the reporter informed through the private report. We will work on fixes or mitigations within our volunteer capacity; we cannot guarantee response or resolution times.

We ask researchers to allow reasonable time for investigation and remediation. We will discuss a disclosure date together, taking severity and any active exploitation into account, rather than requesting indefinite secrecy. When a vulnerability is confirmed, we will coordinate publication of the affected versions, impact and available fix or mitigation through a GitHub security advisory. We will credit the reporter if they wish, using their preferred name.

## Good-faith research

For research within the scope of this policy, we consider good-faith testing authorized as far as Ikigaido can authorize it. We will not initiate or support legal action for such research or inadvertent, good-faith violations of this policy. To the extent we control them, we waive terms-of-use restrictions and claims about circumventing technical protections that would prevent this research.

These commitments apply only to rights and claims controlled by Ikigaido. They do not authorize access to someone else's systems or bind third parties or authorities. If a third party challenges research covered by this policy, we will clarify our authorization.

## Voluntary contributions and bounties

Ikigaido is a non-profit organization with limited resources. We maintain this project in our free time. We are sorry that we cannot offer monetary bounties or financial rewards for vulnerability reports. We greatly appreciate security researchers and ethical hackers who volunteer their time and expertise to help make this project safer.
