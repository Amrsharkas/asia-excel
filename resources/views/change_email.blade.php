<!DOCTYPE html>
<html>
    <head>
        <title>Laravel</title>

        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                padding: 0;
                width: 100%;
                display: table;
                font-weight: 100;
                font-family: 'Lato';
            }

            .container {
                text-align: center;
                display: table-cell;
                vertical-align: middle;
            }

            .content {
                text-align: center;
                display: inline-block;
            }

            .title {
                font-size: 96px;
            }
        </style>
    </head>
    <body>
        <div class="container">
          <form action="/email" method="post" enctype="multipart/form-data" name="form1">
          <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <label for="fileField"></label>
            <input type="file" name="file" id="file">
            <input type="submit" name="button" id="button" value="Submit">
          </form>
        </div>
    </body>
</html>
