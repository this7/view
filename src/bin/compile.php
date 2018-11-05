<!DOCTYPE html>
<html>
<head>
    <title>编译模式</title>
    <script type="text/javascript" src="<?php echo ROOT . "/vendor/this7/view/src/bin/jquery.min.js"; ?>"></script>
    <script type="text/javascript" src="<?php echo ROOT . "/vendor/this7/view/src/bin/babel.js"; ?>"></script>
</head>
<body>
</body>
<?php
$i = 0;
foreach ($compontent as $key => $var): ?>
<script type="text/this7js" id="js_<?php echo $i; ?>" page="<?php echo $var['page']; ?>" name="<?php echo $var['name']; ?>" line="<?php echo $var['line']; ?>">
<?php echo str_repeat(PHP_EOL, $var['line']); ?>
<?php if (isset($var['script'])): ?>
    <?php echo $var['script']; ?>
<?php else: ?>
    export default {
        "name":'<?php echo $var['name']; ?>'
    }
<?php endif;?>
</script>
<script type="text/this7tpl" id="tpl_<?php echo $i; ?>" page="<?php echo $var['page']; ?>" name="<?php echo $var['name']; ?>" line="<?php echo $var['line']; ?>">
<?php echo $var['template']; ?>
</script>
<?php
$i++;
endforeach;?>

<?php if (isset($extend)): ?>
<?php
$i = 0;
foreach ($extend as $key => $var): ?>
<script type="text/this7js" id="extendjs_<?php echo $i; ?>" page="<?php echo $var['page']; ?>" name="<?php echo $var['name']; ?>" line="<?php echo $var['line']; ?>">
<?php echo str_repeat(PHP_EOL, $var['line']); ?>
<?php if (isset($var['script'])): ?>
    <?php echo $var['script']; ?>
<?php else: ?>
    export default {
        "name":'<?php echo $var['name']; ?>'
    }
<?php endif;?>
</script>
<script type="text/this7tpl" id="extendtpl_<?php echo $i; ?>" page="<?php echo $var['page']; ?>" name="<?php echo $var['name']; ?>" line="<?php echo $var['line']; ?>">
<?php echo $var['template']; ?>
</script>
<?php
$i++;
endforeach;?>
<?php endif;?>

<script type="text/this7css" id="this7_style">
    <?php foreach ($baseCss as $key => $value): ?>
    <?php echo $value; ?>
    <?php endforeach;?>
    <?php foreach ($style as $key => $value): ?>
    <?php echo $value; ?>
    <?php endforeach;?>
</script>

<script type="text/javascript">
    function Base64() {

    // private property
    _keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

    // public method for encoding
    this.encode = function (input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;
        input = _utf8_encode(input);
        while (i < input.length) {
            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);
            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;
            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }
            output = output +
            _keyStr.charAt(enc1) + _keyStr.charAt(enc2) +
            _keyStr.charAt(enc3) + _keyStr.charAt(enc4);
        }
        return output;
    }

    // public method for decoding
    this.decode = function (input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;
        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
        while (i < input.length) {
            enc1 = _keyStr.indexOf(input.charAt(i++));
            enc2 = _keyStr.indexOf(input.charAt(i++));
            enc3 = _keyStr.indexOf(input.charAt(i++));
            enc4 = _keyStr.indexOf(input.charAt(i++));
            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;
            output = output + String.fromCharCode(chr1);
            if (enc3 != 64) {
                output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
                output = output + String.fromCharCode(chr3);
            }
        }
        output = _utf8_decode(output);
        return output;
    }

    // private method for UTF-8 encoding
    _utf8_encode = function (string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";
        for (var n = 0; n < string.length; n++) {
            var c = string.charCodeAt(n);
            if (c < 128) {
                utftext += String.fromCharCode(c);
            } else if((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            } else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        }
        return utftext;
    }

    // private method for UTF-8 decoding
    _utf8_decode = function (utftext) {
        var string = "";
        var i = 0;
        var c = c1 = c2 = 0;
        while ( i < utftext.length ) {
            c = utftext.charCodeAt(i);
            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            } else if((c > 191) && (c < 224)) {
                c2 = utftext.charCodeAt(i+1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            } else {
                c2 = utftext.charCodeAt(i+1);
                c3 = utftext.charCodeAt(i+2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }
        }
        return string;
    }
}

function iGetInnerText(testStr) {
    var resultStr = testStr.replace(/[\r\n]/g, ""); //去掉回车换行
    return resultStr;
}
</script>

<script type="text/javascript">
    var js = <?php echo to_json($js); ?>;
    var css = <?php echo to_json($css); ?>;
    var compontents = <?php echo count($compontent); ?>;
    var extens = <?php echo count($extend); ?>;
    var pagename = '<?php echo $page; ?>';


    var exports = [];
    Object.defineProperty(exports, "__esModule", {
        value: true
    });
    var scriptCode = "";

    for (var i = 0; i < extens; i++) {
        var code = $("#extendjs_"+i).text();
        var line = $("#extendjs_"+i).attr("line");
        var page = $("#extendjs_"+i).attr("page");
        var name = $("#extendjs_"+i).attr("name");
        var tpl = $("#extendtpl_"+i).text();
        var script = {
            async: false,
            content: code,
            line:line,
            page:page,
            error: true,
            executed: true,
            loaded: true,
            plugins: null,
            presets: null,
            url: null
        };
        var result3 = Babel.demo(script);
        var result2 = result3+'exports.default.template="'+ iGetInnerText(tpl)+'";var '+name+' = Vue.extend(exports.default);';
        scriptCode += result2;
    }
    for (var i = 0; i < compontents; i++) {
        var code = $("#js_"+i).text();
        var line = $("#js_"+i).attr("line");
        var page = $("#js_"+i).attr("page");
        var name = $("#js_"+i).attr("name");
        var tpl = $("#tpl_"+i).text();
        var script = {
            async: false,
            content: code,
            line:line,
            page:page,
            error: true,
            executed: true,
            loaded: true,
            plugins: null,
            presets: null,
            url: null
        };

        var result3 = Babel.demo(script);




        var result2 = result3+'exports.default.template="'+ iGetInnerText(tpl)+'";Vue.component("'+name+'",exports.default);';
        scriptCode += result2;
    }


    var style = $("#this7_style").text();

    var data = {
        "code":scriptCode,
        "page":pagename,
        "js":js,
        "css":css,
        "style":style,
        "title":'<?php echo $title; ?>',
        "pageTpl":'<?php echo $pageTpl; ?>',
        "precompileTpl":'<?php echo $precompileTpl; ?>',
        "compileTpl":'<?php echo $compileTpl; ?>',
    }

    console.log(data);

    data = JSON.stringify(data);

    var base = new Base64();
    var result = base.encode(data);



    $.ajax({
        url: "<?php echo site_url("system/view/pagestorage"); ?>",
        type:"POST",
        data:{
            code:result
        },
        success: function(e){
           //location.reload();
        }
     });


 //

    // console.log(result3);

    // var jsonString = eval(result3);

    // jsonString.template = tpl;


    //var ddd = Babel.transform(jsonString,options);




 //    console.log(jsonString);

    // var result =     jsonString.toString();



 //    console.log(result);



    //var jsArr = JSON.parse(jsonString);
</script>
</html>