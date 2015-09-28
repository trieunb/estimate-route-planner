$(document).ready(function() {

  function Console(editorElm, codeOuputElm, sqlsOuputElm) {
    this.currentCode = '';
    this.currentOutput = '';
    this.currentSqls = [];
    this.busy = false;

    this.editor = new CodeMirror(editorElm, {
      mode: "text/x-php",
      lineNumbers: true,
      indentUnit: 4,
      indentWithTabs: false,
      matchBrackets: true
    });

    this.$outputContainer = $(codeOuputElm);
    this.$sqlsContainer = $(sqlsOuputElm);

    this.getCode = function() {
      return this.editor.getValue();
    };

    this.setOutput = function(output) {
      this.currentOutput = output;
      var _this = this;
      this.$outputContainer.fadeOut(50, function() {
        _this.$outputContainer.html(output);
        _this.$outputContainer.fadeIn(50);
      });
    };

    this.setSqls = function(sqls) {
      var sqlsTxt = sqls.join("<br/><br/>");
      var _this = this;
      this.currentSqls = sqls;
      if(sqls.length) {
        this.$sqlsContainer.fadeOut(50, function() {
          _this.$sqlsContainer.html(sqlsTxt);
          _this.$sqlsContainer.fadeIn(50);
          hljs.highlightBlock(_this.$sqlsContainer.get(0));
        });
      } else {
        _this.$sqlsContainer.html('');
      }
    };

    this.initHistory = function() {
      localStorage.console_history = JSON.stringify([]);
    };

    this.loadHistory = function() {
      return JSON.parse(localStorage.console_history);
    };

    this.saveHistory = function() {
      var his = this.loadHistory();
      his.push({
        time: Date.now(),
        code: this.currentCode,
        output: this.currentOutput,
        sqls: this.currentSqls
      });
      localStorage.console_history = JSON.stringify(his);
    };

    this.clearCurrentResults = function() {
      this.setOutput('');
      this.setSqls([]);
    };

    this.execute = function() {
      if(this.busy) {
        console.log('busy!');
        return false;
      }

      this.busy = true;
      this.currentCode = this.editor.getValue();
      this.clearCurrentResults();

      var _this = this;
      $.ajax({
        method: 'POST',
        url: 'execute.php',
        data: { code: _this.currentCode },
        success: function(response) {
          var output = '';
          if(response.output) {
            output = response.output;
          }
          var sqls = [];
          if(response.sqls) {
            sqls = response.sqls;
          }
          _this.setOutput(output);
          _this.setSqls(sqls);
          _this.saveHistory();
        },
        error: function(xhr, status, error) {
            if (xhr.responseText) {
                _this.setOutput(xhr.responseText);
            } else {
                alert('Server error!');
            }
            _this.saveHistory();
        },
        complete: function() {
          _this.busy = false;
          $('#btn-execute').attr('disabled', false);
        }
      });
    };

    this.init = function() {
      try {
        if(localStorage.console_history) {
          JSON.parse(localStorage.console_history);
        } else {
          this.initHistory();
        }
      } catch(exp) {
        this.initHistory();
      }

      var his = this.loadHistory();
      if(his.length > 0) {
        var lastEntry = his[his.length -1];
        var lastCode = '';
        initCode = lastEntry.code;
        this.editor.getDoc().setValue(initCode);
      }

    };

    this.init();
  }

  var konsole = new Console($('#editor')[0], '#code-output', '#sqls-output');

  $(document).on('click', '#btn-execute', function(e) {
    if(!konsole.busy) {
      $('#btn-execute').attr('disabled', true);
      konsole.execute();
    }
  });

  $(document).keydown(function (e) {
    if (e.ctrlKey && e.keyCode == 13 && !konsole.busy) {
      $('#btn-execute').attr('disabled', true);
      konsole.execute();
    }
  });

});
