<!DOCTYPE html>
<html>
  <head>
    <title>ERPP Console</title>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.6/styles/default.min.css">
    <link rel="stylesheet" href="./assets/codemirror.css">
    <link rel="stylesheet" href="./assets/style.css">

    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.6/highlight.min.js"></script>
    <script type="text/javascript" src="./assets/codemirror.js"></script>
    <script type="text/javascript" src="./assets/php.js"></script>
    <script type="text/javascript" src="./assets/xml.js"></script>
    <script type="text/javascript" src="./assets/htmlmixed.js"></script>
    <script type="text/javascript" src="./assets/javascript.js"></script>
    <script type="text/javascript" src="./assets/css.js"></script>
    <script type="text/javascript" src="./assets/clike.js"></script>
    <script type="text/javascript" src="./assets/matchbrackets.js"></script>
  </head>
  <body>
    <div class="container">
      <h3 id="title">ERPP Console</h3>
      <div id="wrapper">
        <div class="row">
          <div class="col-lg-6">
            CODE:
            <div id="editor"></div>
            <br />
            <button class="btn btn-primary btn-block btn-lg" id="btn-execute">Execute</button>
          </div>
          <div class="col-lg-6">
            <div class="row">
              <div class="col-lg-12">
                OUPUT:
                <div class="output-container" id="code-output">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-lg-12">
                <br/>
                SQLs:
                <div class="sql output-container" id="sqls-output">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <span class="text-muted">&#169; SFR Software</span>
    </div>
    <script type="text/javascript" src="./assets/scripts.js"></script>
  </body>
</html>
