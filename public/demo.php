<?php
$interfaceList['product'] = include __DIR__ . '/../app/Args/product.php';
$interfaceList['setting'] = include __DIR__ . '/../app/Args/setting.php';
$interfaceList['customer'] = include __DIR__ . '/../app/Args/customer.php';
$interfaceList['order'] = include __DIR__ . '/../app/Args/order.php';
$interfaceList['wechat'] = include __DIR__ . '/../app/Args/wechat.php';

class Request
{
    public function send()
    {
        global $interfaceList;
        $name = isset($_GET['name']) ? $_GET['name'] : '';
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        $server = isset($_GET['server']) ? $_GET['server'] : '';
        $params = isset($_GET['params']) ? $_GET['params'] : [];

        if ($name && $type && $server) {
            include __DIR__ . '/../lib/Client/Client.php';
            $request = Lib\Client\Client::init($name);
            if ($params) {
                list($a, $b) = explode("/", $name);
                $list = $interfaceList[$a][$b]['args'];
                $paramsKeys = array_keys($list);
                foreach ($params as $key => $value) {
                    $request->setParameter($paramsKeys[$key], $value);
                }
            }
            if ($server === 'local') {
                $request->setDebug(true);
            }
            $result = $request->request($type);
            die(json_encode($result));
        }
    }

    public function argsList()
    {
        global $interfaceList;
        $interfaceName = isset($_GET['interfacename']) ? $_GET['interfacename'] : '';
        $html = '<div class="form-group"><label for="" class="col-sm-2 control-label">参数：</label></div>';
        if ($interfaceName) {
            list($a, $b) = explode("/", $interfaceName);
            $list = $interfaceList[$a][$b]['args'];
            foreach($list as $key => $value) {
                $types = explode('|', $value);
                $type = '';
                if (in_array('array',  $types)) {
                    $type = 'array';
                    $html .= '<div class="form-group"><label for="" class="col-sm-2 control-label">' . $key . '：</label><div class="col-sm-2">' .
                        '<textarea  class="form-control arg" ' . 'arg_type="' . $type . '"' . ' placeholder="' . $key . '(' . $value  . ')">' .
                        '</textarea>' .
                        '</div></div>';
                } else {
                    $html .= '<div class="form-group"><label for="" class="col-sm-2 control-label">' . $key . '：</label><div class="col-sm-2">' .
                        '<input type="' . ($value == 'int' ? 'number' : 'text') .
                        '" class="form-control arg" ' . 'arg_type="' . $type . '"' . ' placeholder="' . $key . '(' . $value  . ')">' .
                        '</div></div>';
                }

            }

        }
        die($html);
    }
}

$method = isset($_GET['method']) ? $_GET['method'] : '';
if ($method) {
    $request = new Request();
    switch ($method) {
        case 'request':
            $request->send();
            break;
        case 'args':
            $request->argsList();
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>API 测试DEMO</title>
    <!-- Latest compiled and minified CSS -->
    <link href="http://cdn.bootcss.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
    <!-- Optional theme -->
    <link href="http://cdn.bootcss.com/bootstrap/3.3.5/css/bootstrap-theme.min.css" rel="stylesheet">
</head>
<body>
<div class="container bs-docs-container">
    <div class="row">
        <div class="bs-docs-section">
            <h1 id="forms" class="page-header"><a class="anchorjs-link " href="#forms"
                                                  aria-label="Anchor link for: forms" data-anchorjs-icon=""
                                                  style="font-family: anchorjs-icons; font-style: normal; font-variant: normal; font-weight: normal; position: absolute; margin-left: -1em; padding-right: 0.5em;"></a>Api调试DEMO
            </h1>
            <div class="bs-example" data-example-id="simple-horizontal-form">
                <form class="form-horizontal" onsubmit="return false;">
                    <div class="form-group">
                        <label for="" class="col-sm-2 control-label">选择接口</label>
                        <div class="col-sm-4">
                            <select class="form-control" onchange="getArgsList(this);" id="interfaceName">
                                <option value="" selected="">--请选择--</option>
                                <?php
                                foreach($interfaceList as $k => $list){
                                ?>
                                <optgroup label="<?php echo $k; ?>">
                                <?php
                                    foreach ($list as $key => $value) {
                                ?>
                                    <option value="<?php echo $k."/" . $key; ?>"><?php echo $value['name']; ?></option>
                                <?php }?>
                                    </optgroup>
                                    <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="" class="col-sm-2 control-label">请求方式</label>
                        <div class="col-sm-10">
                            <label class="radio-inline">
                                <input type="radio" name="type" checked="" id="requestType" value="POST"> POST
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="type" id="requestType" value="GET"> GET
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="" class="col-sm-2 control-label">服务</label>
                        <div class="col-sm-10">
                            <label class="radio-inline">
                                <input type="radio" name="server" id="requestServer" value="local"> 本地
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="server" checked="" id="requestServer" value="remote"> 远程
                            </label>
                        </div>
                    </div>
                    <div class="rows" id="appendParams">
                    </div>
                    <div class="form-group">
                        <label for="" class="col-sm-2 control-label">请求结果</label>
                        <div class="col-sm-8">
                            <textarea class="form-control" rows="10" id="requestResult"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default" onclick="request();">提交</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="http://cdn.bootcss.com/jquery/2.1.4/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<script type="text/javascript">
    function addParams() {
        var html = '<div class="form-group"><label for="" class="col-sm-2 control-label"></label><div class="col-sm-2"><input type="text" class="form-control" placeholder="name"></div><div class="col-sm-2"><input type="text" class="form-control" placeholder="value"></div></div>';
        $('#appendParams').append(html);
    }
    function request() {
        var params = [];
        $('#appendParams .arg').each(function (index) {
            if($(this).attr('arg_type') == 'array') {
                if($(this).val()){
                    try {
                        var val = JSON.parse($(this).val());
                    } catch (e){
                        var val = JSON.parse('[]');
                    }
                } else {
                    var val = JSON.parse('[]');
                }
            } else {
                var val = $(this).val();
            }
            params.push(val);
        });
        var data = {
            'method': 'request',
            'name': $('#interfaceName').val(),
            'type': $('#requestType:checked').val(),
            'server': $('#requestServer:checked').val(),
            'params': params
        };
        $.ajax({
            'url': location.href,
            'type': 'GET',
            'data': data,
            'dataType': 'json',
            'success': function (res) {
                var data = '{\r\n';
                if (res.data) {
                    for (var key in res.data) {
                        if(typeof res.data[key] == 'object') {
                            data += key + ':{\r\n';
                            for (var key2 in res.data[key]) {
                                data += "     " + key2 + ':' + JSON.stringify(res.data[key][key2]) + '\r\n';
                            }
                            data += '}\r\n';
                        } else {
                            data += key + ':' + res.data[key] + '\r\n';
                        }

                    }
                }
                data += '}';
                $('#requestResult').val(
                    '消息:' + res.message + "\r\n" +
                        '状态码:' + res.code + "\r\n" +
                        '数据:' + data
                );
            }
        });
    }
    function getArgsList(obj) {
        if ($(obj).val()) {
            $.ajax({
                'url': location.href,
                'type': 'GET',
                'data': {'method': 'args', 'interfacename': $(obj).val()},
                'success': function (res) {
                    if (res) {
                        $('#appendParams').html(res);
                    }
                }
            });
        }
    }
</script>
</body>
</html>