# Contributing

> READ: [Documentation](https://www.mediawiki.org/wiki/Extension:Math)

These steps outline the development process for the MediaWiki Math extension.

The Math extension manages rendering of mathematical expressions in wikis such Wikipedia or private MediaWiki installations.

The wikitext tag math (e.g.,
`<math display=block>E=mc^2</math>`) specifies mathematical expressions in the LaTeX dialalect 
[texvc.](https://github.com/wikimedia/texvcjs/blob/master/lib/parser.pegjs)
The most recent version of the Math extension converts these tags to mathematical expressions that can be
displayed by most browsers using 
[mathoid.](https://gerrit.wikimedia.org/r/#/admin/projects/mediawiki/services/mathoid)

## Workflow overview

The workflow for changing math related aspects is the following

1. Write a user story (What is "broken"? What should be improved? Who is suffering from the current situation? Who would benefit from a the improvent?)
2. Definition of a solution (Creat a mock for a solution. Estimate the effort.)
3. Get Feedback from the community
4. Plan testing and implementation
5. Implement components considering dependencies
6. Get code review
7. Wait for merge
8. Enable feature in beta
9. Enable feature in production (appropriate communities)


Note that for rendering math the following dependent software packages are required
* texvcjs
* texvcinfo (depends on texvcjs)
* mathjax
* mathjax-node (depends on mathjax)
* mathoid (depends on mathjax-node, texvcinfo)
* restbase (depends on mathoid)
* Math extension (depends on either restbase, or [mathoid and a database])
* (MathSearch extension depends on Extension math)
### Step 1

> READ: [How to report a bug](https://www.mediawiki.org/wiki/How_to_report_a_bug)

Math specific points:
* If possible, please make a screenshot.
* Create a LaTeX/PDF file what to demonstrate what you expect. 

Running examples:
1. Have [numbered formulae](https://phabricator.wikimedia.org/T7600).
2. Support for [uppercase greek letters](https://phabricator.wikimedia.org/T218295).
3. Deprecate [non-standard texvc commands](https://phabricator.wikimedia.org/T197842).

### Step 2

Describe the idea how to solve the problem.

* Are there examples / external software that have solved the problem?
* Consider faking screenshots.
* Consider technical and security restrictions

Examples:
1. Numbered equations are hard, since the math extension is not aware of the context. A solution could be to use [CSS counters](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Lists_and_Counters/Using_CSS_counters).
2.  Whitelist the appropriate LaTeX commands in the texvc grammar.
3. Show a warning if any of the non-standard command is used.

### Step 3

Send an email to the [math community group](https://meta.wikimedia.org/wiki/Wikimedia_Community_User_Group_Math) with your request and grant at least two weeks for the community to reply.

Examples:
1. not yet done (Updated 2019-Nov-14)
2. See http://openmath.org/pipermail/wiki-l/2019-August/000000.html
3. fixed before the community wishlist was established.

### Step 4
> NOTE: You'll need [Gerrit access](https://www.mediawiki.org/wiki/Developer_access) to contribute code to this repository.

This extension follows the
[best practices for MediaWiki extensions.](https://www.mediawiki.org/wiki/Best_practices_for_extensions)

Understand the current code and see which components have to be changed in which order.

Examples:
1. Possible tests: phpUnit tests that check if the css attribute is present. Selenium tests that check if numberes are rendered. Find position in the Math extension code that outputs html and find the CSS styles shipped with math. Understand how options are handled by the math extension. Understand PHPUnit and Selenium tests. 
2. Define test cases in MediaWiki, mathoid and texvcjs. Implement node module texvcjs in and release to npm bump version number of texvcinfo, impplement changes in mathoid.
3. Set up a bot to replace old command. Add pseudo latex package to discover formulae that contain deprecated macros. Implement changes in texvcjs, texvcinfo, mathoid, and the math extension.

### Step 5

* Take your time implementing test and components.
* Make sure to consider [best practices for MediaWiki extensions.](https://www.mediawiki.org/wiki/Best_practices_for_extensions)

Examples:
1. Not yet implemented
2. [Step 1: Grammar changes](https://github.com/wikimedia/texvcjs/commit/5b2ce833481616ffcd10502422f7b828a48043f9#diff-84bc28ec886369e8d279d3c006b1134d)
3. [Step 1: Remove commands from visual editor](https://gerrit.wikimedia.org/r/#/c/mediawiki/extensions/Math/+/440737/)

### Step 6
* Plan time for code review
* React to each point that reviewers point you to
* Do not self merge your changes
* Be prepared for multiple iterations

Examples:
1. Not done yet
2. Feedback on [Version bump](https://github.com/wikimedia/texvcjs/pull/33#issuecomment-531713364) for grammar change to allow for NPM release.
3. See for example comment on the [commit message](https://gerrit.wikimedia.org/r/#/c/mediawiki/extensions/Math/+/440737/) in
### Step 7
* Even after the code review has passed it might take some time for the changes to get merged and deployed.

1. Bug filed in 2006 nothing happend so far
2. Bug filed in Jun 2018, mostly implemented but still not finalized.
3. Bug filed on 2019-Mar-14. Feature availible on Wikipedia.org on 2019-11-13

### Step 8 (larger changes)
* For larger changes submit changes to Wikimedia config to enable fist in the beta cluster

1. Not yet done
2. Not applicable
3. Not applicable

### Step 9 (larger changes)
1. Not yet done
2. Not applicable
3. Not applicable

## Updating Translations
Translations are edited through translatewiki.net. To edit a translation, 
[visit this page,](https://translatewiki.net/w/i.php?title=Special:Translate&filter=&group=ext-math&optional=1&task=custom&language=de&action=translate)
select the language you wish to translate to, and proceed to edit a translation. The translations will be merged into the repository automatically by a bot (l10n-bot).

