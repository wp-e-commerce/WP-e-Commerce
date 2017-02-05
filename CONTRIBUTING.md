# Contributing to WP eCommerce

Howdy! We're glad you're interested in contributing to WP eCommerce. Read on to make sure you follow all the steps required to successfully submit an issue or patch to the repository.

*This is not a place to submit support requests, this is a code repository to submit code bugs and code patches. Support tickets can be created here: https://wordpress.org/support/plugin/wp-e-commerce*

Before you submit an issue
---
1. Check for duplicate issues in the repo
2. Make sure you have the latest version of WP eCommerce running in your local enviroment
3. Fork the WP eCommerce repo in order to create Pull Requests.

Reporting a bug
---
1. Specify the version number for both WordPress and WP eCommerce
2. Describe the issue with great detail, be specific about the problem you see.
3. If this is a browser issue, make sure to mention which browser you have trouble on.
4. If this is a visual bug, please make sure to add a screenshot. 
5. if you create a Pull Request for this issue, make sure to attach the Pull Request created to the issue.

Branch Strategy
---
Master will be in sync with the latest release, which will also have its own branch (`branch-x.x.x`). Features should go in `features/*` branches, which then merge into `branch-x.x.x` branch, which then merges to master upon release. Our goal is to keep from merging directly to master. To determine the next branch to fork/PR, review the [Development status](https://github.com/wp-e-commerce/WP-e-Commerce#development-status).

Resources
---
[Labels and Workflow](https://github.com/wp-e-commerce/WP-e-Commerce/wiki/Issue-Labels-and-Workflow)

