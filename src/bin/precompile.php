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
    <?php foreach ($baseCss as $key => $value): ?>
        <?php echo $value; ?>
    <?php endforeach;?>
    <?php foreach ($style as $key => $value): ?>
        <?php echo $value; ?>
    <?php endforeach;?>
    </style>
    <script type="text/javascript">
    var $_SESSION=<?php echo to_json($_SESSION); ?>;
    var app = {}
    var bus = new Vue();
    var exports={};
    var routerView=[];
    Object.defineProperty(exports, "__esModule", {
        value: true
    });
    </script>
</head>

<body>
    <div id="app">
        <app></app>
    </div>
</body>
<?php foreach ($compontent as $key => $value): ?>
    <script type="text/babel" page="<?php echo $value['page']; ?>" >
<?php if (isset($value['line']) && $value['line'] > 0): ?>
    <?php echo str_repeat(PHP_EOL, $value['line'] - 1); ?>
<?php endif;?>
<?php if (isset($value['script'])): ?>
    <?php echo $value['script']; ?>
<?php else: ?>
    export default {
        "name":'<?php echo $value['name']; ?>'
    }
<?php endif;?>

        exports.default.template="<?php echo $value['template']; ?>";
        Vue.component('<?php echo $value['name']; ?>',exports.default);
    </script>
<?php endforeach;?>
<?php if (isset($extend)): ?>
<?php foreach ($extend as $key => $value): ?>
    <script type="text/babel" page="<?php echo $value['page']; ?>" >
<?php if (isset($value['line']) && $value['line'] > 0): ?>
    <?php echo str_repeat(PHP_EOL, $value['line'] - 1); ?>
<?php endif;?>
<?php if (isset($value['script'])): ?>
    <?php echo $value['script']; ?>
    <?php else: ?>
    export default {
        "name":'<?php echo $value['name']; ?>'
    }
    <?php endif;?>
    exports.default.template="<?php echo $value['template']; ?>";
    var <?php echo $value['name']; ?> = Vue.extend(exports.default);
    </script>
<?php endforeach;?>
<?php endif;?>
<script type="text/babel" page="app.html">
    exports.default = {
        el: '#app',
    }
    app = new Vue(exports.default);
</script>
</html>