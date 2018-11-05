<!doctype html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,minimal-ui">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="no">
    <title><?php echo $title; ?></title>
    <?php foreach ($css as $key => $value): ?>
    <link rel="stylesheet" type="text/css" href="<?php echo replace_url($value, 'file'); ?>">
    <?php endforeach;?>
    <?php foreach ($js as $key => $value): ?>
    <script type="text/javascript" src="<?php echo replace_url($value, 'file'); ?>"></script>
    <?php endforeach;?>
    <style type="text/css">
    <?php echo $style; ?>
    </style>
</head>

<body>
    <div id="app">
        <app></app>
    </div>
</body>
<script type="text/javascript">
    var $_SESSION=<?php echo to_json($_SESSION); ?>;
    var app = {};
    var bus = new Vue();
    var exports={};
    var routerView=[];
    Object.defineProperty(exports, "__esModule", {
        value: true
    });
    <?php echo $code; ?>
    exports.default = {
        el: '#app',
    }
    app = new Vue(exports.default);
</script>
</html>