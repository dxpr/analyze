Contributing Guidelines
=======================

### Git Workflow

1. Every pull request must be linked to an issue, without any
   exceptions.
2. Ensure that your branch contains logical [atomic
   commits](https://www.pauline-vos.nl/atomic-commits/).
3. Write commit messages following the [alphagov Git
   styleguide](https://github.com/alphagov/styleguides/blob/master/git.md).
4. Pull requests must contain a short description of your
   solution.
5. Branch naming convention: person/target-branch/#issue-
   description-of-branch.
    1. person - The name of the owner of the branch. For
       example, Jur, Rokaya, Shaaer, Denis, etc.
    2. target-branch - A reference to the target branch you
       want to merge into.
    3. #issue - Every branch must be linked to a GitHub issue.
       Enter the issue number here.
    4. description-of-branch - Describe what's inside, for
       example, "fix-for-jumping-controls-bug" or
       "new-icon-set-for-parameter-definition".
6. All new JavaScript code must be ES6+. No "var" or jQuery.
7. Pull requests must not contain any formatting or
    refactoring changes that are outside the scope of the
    issue.

### Code Ownership

@jjroelofs is the code owner in this repository, and no pull
requests can be merged without his review.

### Coding Standards

1. Follow the [Drupal coding
   standards](https://www.drupal.org/docs/develop/standards).
2. Use the [Airbnb JavaScript coding standards](https://github.com/airbnb/javascript)
3. Ensure compatibility with PHP [8.3 and
   higher](https://github.com/dxpr/analyze/blob/1.0.x/scripts/run-drupal-lint.sh#L9).

Coding standards are automatically checked when you create a
Pull Request. You can also run code linters locally using the
instructions [here](https://github.com/dxpr/analyze#code-linters).

### The Ultimate Checklist Before Requesting a Review

1. In the first round, implement the feature, fix the bug, etc.
2. Test the changes carefully.
3. Do a cleanup/enhancements/refactoring round.
4. Make sure the same tests you did in step No. 2 are still
   valid.
5. Commit your code and push. Keep the git commits' hygiene.
6. Check the automatic Linter checks come back OK.
7. Comment `/qa-demo-2x-bs3-tests` on your PR to trigger
   the regression test suite. Check there are no errors.
9. Request a review after making sure the checks are not
   having unexpected failures.

### Interpreting Regression Test Failures

![Troubleshooting Regression Test Failures](https://github.com/dxpr/analyze/assets/904576/edbb052b-f148-401b-a88d-689b2065f4fe)

View on YouTube: https://youtu.be/X2iEonuiuB4

### Managing Dependencies between analyze and dxpr_maven

When working on changes in the analyze that require
other changes in the dxpr_maven repository, follow this
workflow for better developer experience and efficiency.

Consider that you have created a pull request fixing a bug in
the builder, and a related test needs to be updated. Assume
the branch name: `dxpruser/2.x/#123-fix-section-element-bug`.

1. The author of the analyze pull request should
   communicate the required changes to the QA Engineers.
      - The author should mention "QA test changes" in the PR.
3. The author should share the branch name —
   `dxpruser/2.x/#123-fix-section-element-bug` — where the
   fixes were made for the QA Engineer to work against.
4. The assigned QA Engineer should apply the required changes
   as follows:
    - Switch to the provided branch name on the local
      analyze clone.
    - Start working on the dxpr_maven changes and test them
      locally.
    - After finishing, create a pull request but do not merge
      it.
    - Share the branch name with the analyze pull
      request author.
5. The analyze pull request author should test the
   changes and ensure that the dxpr_maven changes work.
6. A reference to the dxpr_maven pull request should be added
   in the analyze pull request.
7. Request a review.
8. After the reviewer approves the analyze pull request
   and merges it, only then should the dxpr_maven pull
   request be reviewed and merged.
