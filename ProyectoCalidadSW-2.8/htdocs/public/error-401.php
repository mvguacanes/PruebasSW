<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>Dolibarr 401 error page</title>
  </head>

  <body>
    <h1>Error</h1>

    <br>
    Sorry. You are not allowed to access this resource.

    <br>
    <?php print isset($_SERVER["HTTP_REFERER"])?'You come from '.$_SERVER["HTTP_REFERER"].'.':''; ?>

    <hr>
  </body>
</html>
