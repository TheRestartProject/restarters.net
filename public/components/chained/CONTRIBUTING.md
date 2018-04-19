# Contributing to Chained

## Only one feature or change per pull request

Make pull requests only one feature or change at the time. For example you have fixed a bug. You also have optimized some code. Optimization is not related to a bug. These should be submitted as separate pull requests. This way I can easily choose what to include. It is also easier to understand the code changes. Commit messages should be descriptive and full sentences.

Do not commit minified versions. Do not touch the version number. Make the pull requests againts [1.x.x branch](https://github.com/tuupola/jquery_chained/tree/1.x.x)

## Write meaningful commit messages

Proper commit message is full sentence. It starts with capital letter but does not end with period. Headlines do not end with period. The GitHub default `Update filename.js` is not enough. When needed include also longer explanation what the commit does.

```
Capitalized, short (50 chars or less) summary

More detailed explanatory text, if necessary.  Wrap it to about 72
characters or so.  In some contexts, the first line is treated as the
subject of an email and the rest of the text as the body.  The blank
line separating the summary from the body is critical (unless you omit
the body entirely); tools like rebase can get confused if you run the
two together.
```

When in doubt see Tim Pope's blogpost [A Note About Git Commit Messages](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html)

## Follow the existing coding standards

When contributing to open source project it is polite to follow the original authors coding standars. They might be different than yours. It is not a holy war. Just follow then original.

```javascript
var snake_case = "something";

function camelCase(options) {
}

if (true !== false) {
    console.log("here be dragons");
}
```

## Make sure tests pass

After you change some code make sure all the test still pass. You can run tests from the commandline with the following:

```
npm install
grunt test
```

Or you can also open the [test runner](https://github.com/tuupola/jquery_chained/blob/master/test/SpecRunner.html) in browser.
