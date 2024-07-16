<template>
	<div class="main">
		<el-row :gutter="12">
			<el-col :md="19" >
				<el-row :gutter="12">
					<el-col :md="24">
						<el-card class="lattice">
							<el-row :gutter="12">
								<el-col :span="6">
									<div class="group" @click="switchPage('mot')">
										<b>{{lattice.sve.today}}</b>
										<small>{{lattice.sve.yesterday}}%</small>
										<p>日销售额</p>
										<i class="ico el-icon-shopping-cart-full" attr="c1"></i>
									</div>
								</el-col>
								<el-col :span="6">
									<div class="group" @click="switchPage('mpt')">
										<b>{{lattice.spt.today}}</b>
										<small>{{lattice.spt.yesterday}}%</small>
										<p>日销售毛利</p>
										<i class="ico el-icon-money" attr="c2"></i>
									</div>
								</el-col>
								<el-col :span="6">
									<div class="group" @click="switchPage('wds')">
										<b>{{lattice.nos.today}}</b>
										<small>{{lattice.nos.yesterday}}%</small>
										<p>日销售笔数</p>
										<i class="ico el-icon-pie-chart" attr="c3"></i>
									</div>
								</el-col>
								<el-col :span="6">
									<div class="group" @click="switchPage('cbf')">
										<b>{{lattice.fund.today}}</b>
										<small>{{lattice.fund.yesterday}}%</small>
										<p>日资金收入</p>
										<i class="ico el-icon-coin" attr="c4"></i>
									</div>
								</el-col>
							</el-row>
						</el-card>
					</el-col>
					<el-col :md="10">
						<el-card class="often">
							<div slot="header">
								<span>常用功能</span>
							</div>
							<el-carousel class="carousel" :autoplay="false" arrow="never" height="180px">
								<template v-if="often.length==0">
									<p class="empty" @click="switchPage('often')"><i class="el-icon-thumb"></i>还未设置常用功能，点此前往。</p>
								</template>
								<template v-else>
									<template v-for="vo of often">
										<el-carousel-item>
											<el-row :gutter="12">
												<template v-for="v of vo">
													<el-col :span="6">
														<i class="ico el-icon-connection" @click="switchPage(v.key)"></i>
														<span @click="switchPage(v.key)">{{v.name}}</span>
													</el-col>
												</template>
											</el-row>
										</el-carousel-item>
									</template>
								</template>
							</el-carousel>
						</el-card>
					</el-col>
					<el-col :md="14">
						<el-card class="summary">
							<div slot="header">
								<span>汇总信息</span>
							</div>
							<el-row :gutter="12">
								<el-col :span="8">
									<div class="item" @click="switchPage('goods')">
										<span>商品总数</span>
										<p>{{summary.goods}}</p>
										<i class="ico el-icon-goods"></i>
									</div>
								</el-col>
								<el-col :span="8">
									<div class="item" @click="switchPage('customer')">
										<span>客户总数</span>
										<p>{{summary.customer}}</p>
										<i class="ico el-icon-user"></i>
									</div>
								</el-col>
								<el-col :span="8">
									<div class="item" @click="switchPage('supplier')">
										<span>供应商总数</span>
										<p>{{summary.supplier}}</p>
										<i class="ico el-icon-shopping-cart-full"></i>
									</div>
								</el-col>
								<el-col :span="8">
									<div class="item" @click="switchPage('stock')">
										<span>库存总数</span>
										<p>{{summary.room}}</p>
										<i class="ico el-icon-pie-chart"></i>
									</div>
								</el-col>
								<el-col :span="8">
									<div class="item" @click="switchPage('stock')">
										<span>库存预警</span>
										<p>{{summary.rwg}}</p>
										<i class="ico el-icon-document"></i>
									</div>
								</el-col>
								<el-col :span="8">
									<div class="item" @click="switchPage('batch')">
										<span>保质期预警</span>
										<p>{{summary.bwg}}</p>
										<i class="ico el-icon-tickets"></i>
									</div>
								</el-col>
							</el-row>
						</el-card>
					</el-col>
					<el-col :md="24">
						<el-card class="chart">
							<div slot="header">
								<span>数据概览</span>
							</div>
							<el-carousel class="carousel" :autoplay="false" arrow="never" height="0px" @change="dataChartChange">
								<template v-for="o of option">
									<el-carousel-item></el-carousel-item>
								</template>
							</el-carousel>
							<div id="dataChart"></div>
						</el-card>
					</el-col>
					<el-col :md="8">
						<el-card class="assets">
							<div slot="header">
								<span>资产数据</span>
							</div>
							<font>总资产</font>
							<span>{{assets.all}}</span>
							<p attr='c1' @click="switchPage('cbf')">资金余额:<b>{{assets.account}}</b>元</p>
							<p attr='c2' @click="switchPage('wbs')">库存成本:<b>{{assets.rsy}}</b>元</p>
							<p attr='c3' @click="switchPage('crs')">应收欠款:<b>{{assets.cas}}</b>元</p>
							<p attr='c4' @click="switchPage('cps')">应付欠款:<b>{{assets.sas}}</b>元</p>
						</el-card>
					</el-col>
					<el-col :md="16">
						<el-card class="fund">
							<div slot="header">
								<span>资金数据</span>
							</div>
							<div id="fundChart"></div>
						</el-card>
					</el-col>
				</el-row>
			</el-col>
			<el-col :md="5">
				<el-card class="guide">
					<div slot="header">
						<span>新手向导</span>
						<a :href="store.base.webSite" target="_blank">
							<el-tooltip content="官方社区" effect="light">
								<i class="r-ico el-icon-question"></i>
							</el-tooltip>
						</a>
					</div>
					<p>三分钟向导学习，快速上手使用。</p>
					<el-link type="primary" @click="switchPage('guide')">查看向导</el-link>
				</el-card>
				<el-card class="notice">
					<div slot="header">
						<span>公告信息</span>
					</div>
					<template v-if="notice==''">
						<div class="info" @click="switchPage('sys')">
							<p class="empty">:) 点此设置公告信息</p>
						</div>
					</template>
					<template v-else>
						<div class="info" v-html="notice"></div>
					</template>
				</el-card>
				<el-card class="load">
					<div slot="header">
						<span>负载监测</span>
					</div>
					<div>
						<div class="group">
							<div class="flex">
								<span class="nums">{{load.cache.rate}}%</span>
								<el-tooltip content="点击清空缓存" effect="light">
									<span class="text" @click="clachCache">缓存量[{{load.cache.size}}M]</span>
								</el-tooltip>
							</div>
							<el-progress :percentage="load.cache.rate" color="#5FB878" :show-text="false"></el-progress>
						</div>
						<div class="group">
							<div class="flex">
								<span class="nums">{{load.mysql.rate}}%</span>
								<span class="text">数据量[{{load.mysql.size}}M]</span>
							</div>
							<el-progress :percentage="load.mysql.rate" color="#5FB878" :show-text="false"></el-progress>
						</div>
					</div>
				</el-card>
				<el-card class="wechat">
					<div slot="header">
						<span>客服支持</span>
					</div>
					<span>打开微信扫码或搜索点可云，享受7*12线上服务支持。</span>
					<img src="/static/images/lib/wechat.png">
					<el-link type="primary" :underline="false" :href="store.base.cdnSite+'/lib/package/desktop.zip'">下载桌面版框架程序</el-link>
				</el-card>
				<el-card class="run">
					<div slot="header">
						<span>运行环境</span>
					</div>
					<table>
						<tr>
							<td>系统类型</td>
							<td>{{run.os}}</td>
						</tr>
						<tr>
							<td>解译引擎</td>
							<td>{{run.soft}}</td>
						</tr>
						<tr>
							<td>PHP版本</td>
							<td>{{run.php}}</td>
						</tr>
						<tr>
							<td>MYSQL版本</td>
							<td>{{run.mysql}}</td>
						</tr>
						<tr>
							<td>通信协议</td>
							<td>{{run.protocol}}</td>
						</tr>
						<tr>
							<td>程序版本</td>
							<td>{{store.base.ver}}</td>
						</tr>
					</table>
				</el-card>
			</el-col>
		</el-row>
	</div>
</template>
<script>
	var chart={};
	import * as echarts from 'echarts';
	export default {
		name: "Main",
		data() {
			return {
				lattice:{
					sve:{today:0,yesterday:0},
					spt:{today:0,yesterday:0},
					nos:{today:0,yesterday:0},
					fund:{today:0,yesterday:0}
				},
				summary:{
					goods:0,
					customer:0,
					supplier:0,
					room:0,
					rwg:0,
					bwg:0
				},
				assets:{
					all:0,
					account:0,
					rsy:0,
					cas:0,
					sas:0
				},
				load:{
					cache:{size:0,rate:0},
					mysql:{size:0,rate:0}
				},
				run:{
					'os':'',
					'soft':'',
					'php':'',
					'mysql':'',
					'protocol':''
				},
				option:[]
			};
		},
		created() {
			this.record();
		},
		mounted(){
			this.initChart();
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			},
			often(){
				return this.$lib.chunk(this.store.often,8);
			},
			notice(){
				return this.store.sys.notice.replace(/\n/g,"<br>");
			}
		},
		methods: {
			//获取数据
			record(){
				this.$axios.post("main/record").then(result => {
					if (result.state == "success") {
						this.lattice=result.info.lattice;
						this.summary=result.info.summary;
						this.assets=result.info.assets;
						this.option=result.info.option;
						this.load=result.info.load;
						this.run=result.info.run;
						this.dataChartChange(0);
						result.info.fund.series[0].itemStyle.color=(d)=>{
							return "#"+Math.floor(Math.random()*(256*256*256-1)).toString(16);
						}
						chart.fund.setOption(result.info.fund);
					} else if (result.state == "error") {
						this.$message({
							type: "warning",
							message: result.info
						});
					} else {
						this.$message({
							type: "error",
							message: "[ ERROR ] 服务器响应超时!"
						});
					}
				});
			},
			//清空缓存
			clachCache(){
				this.$confirm("您确定要清除缓存数据吗?", "提示", {
					confirmButtonText: "确定",
					cancelButtonText: "取消",
					type: "warning"
				}).then(() => {
					this.$axios.post("service/clachCache").then(result => {
						if (result.state == "success") {
							this.$bus.emit('homeReload',this.$options.name);
							this.$message({
								type: "success",
								message: "清除缓存成功!"
							});
							
						} else if (result.state == "error") {
							this.$message({
								type: "warning",
								message: result.info
							});
						} else {
							this.$message({
								type: "error",
								message: "[ ERROR ] 服务器响应超时!"
							});
						}
					});
				});
			},
			//数据图表切换
			dataChartChange(index){
				chart.data.setOption(this.option[index],true);
			},
			//初始化报表
			initChart(){
				chart.data=echarts.init(document.getElementById('dataChart'));
				chart.fund=echarts.init(document.getElementById('fundChart'));
				window.onresize = ()=>{
					chart.data.resize();
					chart.fund.resize();
				}
			},
			//切换页面
			switchPage(key){
				this.$bus.emit('switchPage',key,true);//切换页面
			}
		}
	};
</script>

<style scoped>
	.el-menu .is-active{
		border-left: 2px solid #409eff;
	}
	.el-card >>> .el-card__header{
		padding: 12px;
	}
	.el-card .r-ico{
		float: right;
		color: #4c4c4c;
	}
	.el-card >>> .el-card__body{
		padding: 12px;
		box-sizing: border-box;
	}
	/* 格子汇总 */
	.lattice{
		margin-bottom: 12px;
	}
	
	.lattice >>> .el-card__body{
		height: 91px;
		padding: 20px 12px;
	}
	.lattice .el-col{
		border-right: 1px dashed #e4e7ed;
		position: relative;
	}
	.lattice .el-col:last-child{
		border-right: none;
	}
	.lattice .group{
		
		padding-left: 12px;
	}
	.lattice b{
		position: relative;
		color: #000;
		font-size: 22px;
		margin-right: 4px;
		z-index: 2;
		font-family: impact;
		font-weight: initial;
	}
	.lattice small{
		position: relative;
		color: #5fb878;
		z-index: 2;
	}
	.lattice p{
		position: relative;
		color: #999;
		margin-top: 4px;
		z-index: 2;
	}
	.lattice .ico{
		position: absolute;
		right: 12px;
		font-size: 36px;
		bottom: 0;
		z-index: 1;
	}
	.lattice .ico[attr='c1']{
		color: rgba(33,150,243,0.5);
	}
	.lattice .ico[attr='c2']{
		color:rgba(76,175,80,0.5);
	}
	.lattice .ico[attr='c3']{
		color:rgba(244,67,54,0.5);
	}
	.lattice .ico[attr='c4']{
		color:rgba(255,152,0,0.5);
	}
	/* 常用功能 */
	.often{
		position: relative;
	}
	.often .carousel span{
		text-align: center;
		display: block;
		color: #666;
		line-height: 32px;
		white-space: nowrap;
		overflow: hidden;
	}
	.often .empty{
		text-align: center;
		line-height: 180px;
		letter-spacing: 1px;
		color: #555;
	}
	.often .empty i{
		margin-right: 6px;
	}
	.often .carousel .ico{
		width: 100%;
		height: 60px;
		text-align: center;
		padding-top: 20px;
		box-sizing: border-box;
		background: #F8F8F8;
		font-size: 24px;
		border-radius: 2px;
		color: #5f5f5f;
	}
	/* 数据汇总 */
	.summary{
		position: relative;
	}
	.summary .item{
		position: relative;
		background: #F8F8F8;
		height: 78px;
		margin-bottom: 12px;
		border-radius: 2px;
		padding: 10px 15px;
		box-sizing: border-box;
		overflow: hidden;
	}
	.summary .item span{
		font-size: 12px;
		color: #999;
	}
	.summary .item p{
		font-size: 24px;
		color: #009688;
		line-height: 48px;
	}
	.summary i{
		position: absolute;
		right: 6px;
		bottom: 6px;
		font-size: 32px;
		color:rgba(107,107,107,0.2);
	}
	/* 图表信息 */
	.chart{
		margin-top: 12px;
	}
	.chart{
		position: relative;
	}
	.chart #dataChart{
		position: relative;
		width: 100%;
		height: 320px;
	}
	/* 资产数据 */
	.assets{
		margin-top: 12px;
	}
	.assets >>>.el-card__body{
		height: 264px;
	}
	.assets font{
		color: #999;
		display: block;
		margin-left: 12px;
		margin-bottom: 12px;
	}
	.assets >>>.el-card__body span{
		display: block;
		font-size: 16px;
		font-weight: bold;
		margin-left: 12px;
		margin-bottom: 12px;
	}
	.assets p{
		color: #999;
		line-height: 28px;
		padding-left: 10px;
		margin-bottom: 12px;
		border-style: solid;
		border-width: 2px;
		border-top: none;
		border-right: none;
		border-bottom: none;
		cursor: pointer;
	}
	.assets p[attr='c1']{
		border-color: #2196F3;
	}
	.assets p[attr='c2']{
		border-color: #4caf50;
	}
	.assets p[attr='c3']{
		border-color: #f44336;
	}
	.assets p[attr='c4']{
		border-color: #ff9800;
	}
	.assets b{
		margin-left: 32px;
		margin-right: 2px;
		font-weight: initial;
	}
	/* 资金数据 */
	.fund{
		margin-top: 12px;
	}
	.fund #fundChart{
		height: 240px;
	}
	/* 新手向导 */
	.guide{
		
	}
	.guide .el-link{
		margin-top: 6px;
	}
	.guide p{
		color: #666;
	}
	
	/* 公告信息 */
	.notice{
		margin-top: 12px;
	}
	.notice .info{
		color: #666;
		line-height: 24px;
		min-height: 72px;
	}
	.notice .empty{
		text-align: center;
		line-height: 72px
	}
	/* 负载信息 */
	.load{
		margin-top: 12px;
	}
	.load .flex{
		display: flex;
		justify-content: space-between;
		margin-bottom: 6px;
	}
	.load .group{
		margin-bottom: 20px;
	}
	.load .group:last-child{
		margin-bottom: 0;
	}
	.load .nums{
		line-height: 24px;
		font-size: 24px;
		color: #666;
	}
	.load .text{
		line-height: 24px;
		color: #999;
		font-size: 14px;
		margin-bottom: 6px;
		cursor: pointer;
	}
	/* 客服支持 */
	.wechat{
		margin-top: 6px;
	}
	.wechat img{
		width: 50%;
		margin: 0 auto;
		display: block;
	}
	.wechat .el-link{
		display: block;
		text-align: center;
	}
	.wechat span{
		color: #666;
		line-height: 24px;
	}
	
	/* 运行环境 */
	.run{
		margin-top: 12px;
	}
	.run table{
		width: 100%;
		border-collapse:collapse;  
		border-spacing:0;
	}
	.run td{
		color: #666;
		border: 1px solid #e6e6e6;
		padding: 10px 12px;
		font-size: 14px;
	}
	/* 轮播 */
	.carousel{
		position: initial;
	}
	.carousel >>> .el-carousel__indicators{
		top: 8px;
		right: 12px;
		left: initial;
		bottom: initial;
		transform: initial;
	}
	.carousel >>> .el-carousel__button{
		background: #5e5e5e;
		width: 9px;
		height: 9px;
		border-radius: 50%;
	}
	@media only screen and (max-width: 992px){
		.often{
			margin-bottom: 12px;
		}
		.fund{
			margin-bottom: 12px;
		}
	}
	@media only screen and (max-width: 660px){
		.lattice .ico{
			color: #fff !important;
		}
	}
</style>
