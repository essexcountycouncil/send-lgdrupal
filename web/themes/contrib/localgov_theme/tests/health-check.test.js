'use strict';

const chai = require('chai'),
      chaiExec = require("@jsdevtools/chai-exec"), // Allows us to assert CLI commands
      assert = chai.assert,
      expect = chai.expect;
const fs = require('fs'), // Node file system
      { execSync } = require("child_process"); // Node exec function that allows us to run other commands

// Register @jsdevtools/chai-exec
chai.use(chaiExec);

/* Returns true or false depending if a directory exists or not */
function checkDirectoryExists(directory) {
  try {
    return fs.existsSync(directory)
  } catch(err) {
    return false
  }
}

/* Alias for checkDirectoryExists() so that code is more readable */
function checkFileExists(file) {
  return checkDirectoryExists(file)
}

/* Returns true if a file contains string */
function fileContains(file, text) {
  try {
    return fs.readFileSync(file, {encoding: 'utf8'}).includes(text)
  } catch (error) {
    return false
  }
}

/* Creates a file using the fs object */
function writeToFile(file, text) {
  fs.writeFile(file, text, function(error) {
    if (error) {
      return error;
    }
  });
}

/* Runs a command synchronously using execSync */
function runCommand(command) {
  execSync(command);
}

/* Removes a file using fs.unlinkSync */
function removeFile(file) {
  fs.unlinkSync(file);
}


describe('Health check: General theme structure', function () {
  describe('Directory structure', function () {
    it('./assets', function(done) {
      assert(checkDirectoryExists('./assets'))
      done()
    });
    it('./assets/css', function(done) {
      assert(checkDirectoryExists('./assets/css'))
      done()
    });
    it('./assets/js', function(done) {
      assert(checkDirectoryExists('./assets/js'))
      done()
    });
    it('./templates', function(done) {
      assert(checkDirectoryExists('./templates'))
      done()
    });
  });

  describe('Checking compiled files exist', function () {
    it('./assets/css/lib/style.css', function(done) {
      assert(checkFileExists('./assets/css/lib/main.css'))
      done()
    });
    it('./assets/js/main.js', function(done) {
      assert(checkFileExists('./assets/js/main.js'))
      done()
    });
  });

  describe('Verifying source control and metadata', function () {
    it('.gitignore exists', function(done) {
      assert(checkFileExists('./.gitignore'))
      done()
    });
    it('node_modules/ is gitignored', function(done) {
      assert(fileContains('./.gitignore', 'node_modules'));
      done()
    });
    it('composer.json', function(done) {
      assert(checkFileExists('./composer.json'))
      done()
    });
    it('LICENSE', function(done) {
      assert(checkFileExists('./LICENSE'))
      done()
    });
    it('package.json', function(done) {
      assert(checkFileExists('./package.json'))
      done()
    });
    it('README.md', function(done) {
      assert(checkFileExists('./README.md'))
      done()
    });
  });

  describe('Verifying Drupal files', function () {
    it('breakpoints.yml', function(done) {
      assert(checkFileExists('./localgov_theme.breakpoints.yml'))
      done()
    });
    it('info.yml', function(done) {
      assert(checkFileExists('./localgov_theme.info.yml'))
      done()
    });
    it('libraries.yml', function(done) {
      assert(checkFileExists('./localgov_theme.libraries.yml'))
      done()
    });
    it('theme PHP file', function(done) {
      assert(checkFileExists('./localgov_theme.theme'))
      done()
    });
  });

  describe('Making sure config files exist', function () {
    it('.stylelintrc', function(done) {
      assert(checkFileExists('./.stylelintrc'))
      done()
    });
  });
});


describe('Health check: Gulp tasks', function () {
  /* Commands */
  const npmGenerate = 'npm run generate';

  /* File paths we will use */
  const scssFile = './assets/scss/mocha-test-file.scss';
  const cssFile = './assets/css/mocha-test-file.css';
  const cssMapFile = './assets/css/mocha-test-file.css.map';

  /* File strings we need */
  const mochaScssString = `
      $mocha-test-hidden-variable: #ccc;
      $mocha-test-variable: bold;
      .mocha-test-string {
        font-weight: $mocha-test-variable;
      }`;

  describe('Testing NPM commands ', function () {
    this.timeout(15000);

    it(`${npmGenerate}`, function(done) {
      let npm = chaiExec(npmGenerate);

      expect(npm).to.have.output.that.contains('Finished \'generateStyle\'');
      // Correctly check for errors on the output
      expect(npm).to.not.have.output.that.contains('Error');
      assert.exitCode(npm, 0);
      done()
    });
  });

  describe('Testing SCSS file compilation ', function () {

    before(function() {
      writeToFile(scssFile, mochaScssString);
    });


    it('Creating a new SCSS file', function(done) {
      this.timeout(5000);

      assert(checkFileExists(scssFile));
      done();
    });

    it('Compile SCSS to CSS', async function(done) {
      this.timeout(15000);

      runCommand(npmGenerate);
      done();
    })

    it('New CSS file exists with correct selectors', function(done) {
      assert(checkFileExists(cssFile));
      assert(fileContains(cssFile, 'mocha-test-string{font-weight:700}'));
      assert(!fileContains(cssFile, '$mocha-test-hidden-variable'));
      done();
    });

    it('CSS map file exists', function(done) {
      assert(checkFileExists(cssMapFile));
      done();
    });

    after(function() {
      removeFile(scssFile);
      removeFile(cssFile);
      removeFile(cssMapFile);
    });
  });
});
