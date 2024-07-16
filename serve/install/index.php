<?php
    header('Content-Type: text/html; charset=utf-8');
    include_once('lib/base.php');
    $soft=require('../config/soft.php');
    $dir = ['../install/','../config/','../runtime/','../static/'];
    $fun = ['mysqli_connect','fsockopen','gethostbyname','file_get_contents','xml_parser_create','mb_strlen','curl_exec'];
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>点可云软件安装向导</title>
		<link rel="stylesheet" type="text/css" href="css/lib.css"/>
	</head>
	<body>
		<header>
			<div class="container">
				<div class="title">
					<span class="name">[ 点可云软件中心 ]</span>
					<span class="product">进销存软件<?php echo $soft['version'] ?></span>
				</div>
			</div>
		</header>
		<main>
			<div class="container" >
				<div class="box">
					<div id="content1">
						<h3 style="text-align: center;line-height: 36px;">最终用户授权许可协议</h3>
						<p style="text-indent: 24px;">感谢您选择点可云系列软件（以下简称本软件），我们致力于让办公简单、轻松、自动化，为云端办公而不懈努力！</p>
						<p style="text-indent: 24px;">本《最终用户授权许可协议》（以下简称本协议）是您（自然人、法人或其他组织）有关安装、复制、修改、使用本软件的法律协议，同时本协议亦适用于任何有关本软件的后期更新和升级。一旦以任何方式使用本软件，即表明您同意接受本协议各项条款的约束。</p>
						<p style="text-indent: 24px;color: #f00;">如果您不同意本协议中的条款，必须删除本软件及相关资料。</p>
						<h4 style="line-height: 36px;">协议许可范围声明：</h4>
						<p style="text-indent: 24px; color: #f00; ">1. 本软件适用于学习与交流用途，不可用于商业用途，商业用途包括但不限于销售，转售，二次开发，OEM等，如需商业用途必须取得专属授权。</p>
						<p style="text-indent: 24px; color: #f00; ">2.本软件允许免费使用，禁止对软件修改后二次发布，构成恶意传播的将一律视为侵权。</p>
						<p style="text-indent: 24px;">3.为了更好服务，本软件可能会收集您的域名等信息，并承诺不会收集除此之外的任何数据信息。</p>
						<h4 style="line-height: 36px;">有限担保和免责声明：</h4>
						<p style="text-indent: 24px;">1.本软件和附带的资料是作为不提供任何明确的或隐含的赔偿或担保的形式提供的。</p>
						<p style="text-indent: 24px;">2.用户出于自愿而使用本软件，您必须了解使用本软件系统的风险，在尚未获得合法授权前，我们不承诺提供任何形式的使用担保、技术支持，同时也不承担任何因使用本软件而产生的问题或损失的相关责任。</p>
						<p style="text-indent: 24px;">3.我方不对使用本软件构建的任何站点的任何信息内容导致的任何版权纠纷和法律争议及后果承担责任。</p>
						<p style="text-indent: 24px;">4.本协议的电子文档形式同双方书面签署协议一样，具有完全的和等同的法律效力。您一旦开始确认本协议并安装本软件，即被视为完全理解并接受本协议的各项条款，在享有上述条款授予的权力的同时受到相关的约束和限制。</p>
						<p style="text-indent: 24px;">5.本协议许可范围以外的行为，将直接违反本协议并构成侵权，我们有权随时终止授权、责令停止损害，并保留追究相关责任的权力。</p>
						<p style="text-indent: 24px;">6.本软件著作权所有者享有最终解释权。</p>
						<a style="float: right;" href="https://www.nodcloud.com" target="_blank">点可云软件中心</a>
						<div style="clear:both"></div>
						<button style="display: block;margin: 0 auto;" onclick="showHash('#2')">我已仔细阅读本协议并同意安装</button>
					</div>
					<div id="content2">
						<p>请检查您的服务器是否支持安装本软件，请在继续安装前消除警告信息。</p>
						<fieldset>
						    <legend>环境检测结果</legend>
						    <p class="success" style="margin-left: 12px;line-height: 32px;">
								<i>&nbsp;&nbsp;&nbsp;&nbsp;</i>
								<strong>解析引擎</strong>
								<span style="margin-left: 36px;color:#009933;"><?php echo $_SERVER['SERVER_SOFTWARE'] ?></span>
							</p>
							<p class="<?php echo PHP_VERSION>=7.3?'success':'warning'?>" style="margin-left: 12px;line-height: 32px;">
								<i>&nbsp;&nbsp;&nbsp;&nbsp;</i>
								<strong>PHP版本</strong>
								<span style="margin-left: 36px;color:#009933;"><?php echo PHP_VERSION ?></span>
							</p>
						</fieldset>
						<fieldset>
						    <legend>目录权限与函数库</legend>
							<p style="text-indent: 24px;">要能正常使用本软件，需要将如下文件或目录清单CHMOD设置为 777 或 666，并启用相关支持函数库。 </p>
							<ul>
                                <?php foreach($dir as $dirVo){?>
                                    <li>
                                        <p class="<?php echo is_writable($dirVo)?'success':'warning'?>">
                                            <span><?php echo $dirVo;?></span>
                                            <i style="margin-left: 20px;">&nbsp;&nbsp;&nbsp;&nbsp;</i>
                                        </p>
                                    </li>
                                <?php }?>
                                <?php foreach($fun as $funVo){?>
                                    <li>
                                        <p class="<?php echo function_exists($funVo)?'success':'warning'?>">
                                            <span><?php echo $funVo;?></span>
                                            <i style="margin-left: 20px;">&nbsp;&nbsp;&nbsp;&nbsp;</i>
                                        </p>
                                    </li>
                                <?php }?>
                                <li>
                                    <p class="<?php echo extension_loaded("pdo_mysql")?'success':'warning'?>">
                                        <span>pdo_mysql</span>
                                        <i style="margin-left: 20px;">&nbsp;&nbsp;&nbsp;&nbsp;</i>
                                    </p>
                                </li>
                                <li>
                                    <p class="<?php echo extension_loaded("openssl")?'success':'warning'?>">
                                        <span>openssl</span>
                                        <i style="margin-left: 20px;">&nbsp;&nbsp;&nbsp;&nbsp;</i>
                                    </p>
                                </li>
							</ul>
						</fieldset>
						<div class="group">
							<button onclick="history.go(0);">重新检测</button>
							<button onclick="showHash('#3')">下一步</button>
						</div>
					</div>
					<div id="content3">
						<p>您需要在下方输入数据库相关配置信息。</p>
						<fieldset>
						    <legend>数据库配置</legend>
							<div class="form">
								<div class="row">
									<span>数据库地址：</span>
									<input type="text" id="dbhost" value="localhost"/>
									<tip>数据库地址，一般无需更改。</tip>
								</div>
								<div class="row">
									<span>数据库名称：</span>
									<input type="text" id="dbname" value=""/>
									<tip>数据库名称，请确保用字母开头。</tip>
								</div>
								<div class="row">
									<span>数据库账号：</span>
									<input type="text" id="dbuser" value=""/>
								</div>
								<div class="row">
									<span>数据库密码：</span>
									<input type="text" id="dbpwd" value=""/>
								</div>
							</div>
						</fieldset>
						<button style="display: block;margin: 0 auto;" id="save" onclick="save();">保存配置并继续</button>
					</div>
					<div id="content4">
						<p style="font-size: 24px;">:) 恭喜您，安装成功。</p>
						<p>从今天开始，点可云软件将为您服务，感谢您的信任与支持。</p>
						<p style="color:#f00;">默认登陆账号:admin 密码:admin888 登陆系统后您可以自行更改密码。</p>
						<p>建议您删除 install 目录，以防止再次安装而覆盖数据。</p>
						<a href="/" target="_parent" style="display: block;">点此登陆系统</a>
					</div>
				</div>
			</div>
		</main>
		<footer>
			<p>点可云系列软件受《中华人民共和国著作权法》保护，版权所有，盗版必究！</p>
		</footer>
		<script src="js/jquery.js" type="text/javascript" charset="utf-8"></script>
		<script src="js/lib.js" type="text/javascript" charset="utf-8"></script>
	</body>
</html>
