<template>
	<div class="summary area">
		<div class="progress">
			<el-progress type="circle" :percentage="per"></el-progress>
		</div>
		<div class="btnGroup">
			<el-button type="primary" @click="init" :disabled="per!=0 && per!=100">开始校准</el-button>
			<el-button type="info" @click="help">获取帮助</el-button>
		</div>
		
		<el-card class="card">
		  <div slot="header" class="clearfix">
		    <span>校准信息：</span>
		  </div>
		  <ul class="list">
			  <template v-for="row in list">
				  <li>{{row}}</li>
			  </template>
		  </ul>
		</el-card>
	</div>
</template>
<script>
	export default {
		name: "Summary",
		data() {
			return {
				list:[
					"1.数据校准将重新统计单据的收发成本。",
					"2.本功能适用于结账确认及单据插单场景。",
					"3.本操作很耗费系统资源，建议闲暇时执行。",
					"4.初始化过程中禁止对系统其他模块进行操作。",
					"5.操作过程中须保持互联网通讯，请勿手动终止。",
					"6.如操作过程中断，需重新执行数据校准。",
					"7.如果在使用过程中遇到问题，请联系客服解决。"
				],
				per:0,
				count:0,
				page:1,
				limit:30
			};
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			}
		},
		methods: {
			//初始化
			init(){
				this.per=0;
				let time=this.$moment().format('HH:mm:ss');
				this.$axios.post("summary/init").then(result => {
					if (result.state == "success") {
						this.count=result.count;
						this.list=[];
						this.list.unshift(time+' --- 开始校准');
						this.list.unshift(time+' --- 计价方法[ '+result.info.valuation+' ]');
						this.list.unshift(time+' --- 核算类型[ '+result.info.branch+' ]');
						this.list.unshift(time+' --- 成本规则[ '+result.info.rule+' ]');
						this.list.unshift(time+' --- 数据条数[ '+result.count+' ]');
						this.poll();
					} else if (result.state == "error") {
						this.list.unshift(time+' --- '+result.info);
					} else {
						this.list.unshift(time+' --- [ ERROR ] 服务器响应超时!');
					}
				});
			},
			//轮询校准
			poll(){
				let time=this.$moment().format('HH:mm:ss');
				this.$axios.post("summary/poll", {
					page: this.page,
					limit: this.limit
				}).then(result => {
					if (result.state == "success") {
						let section=[((this.page-1)*this.limit)+1,((this.page)*this.limit)];
						if(section[1]>this.count){
							this.list.unshift(time+' --- 当前校准[ '+section[0]+' - '+this.count+' ]');
							this.list.unshift(time+' --- 校准完成');
							this.per=100;
							this.page=1;
						}else{
							this.list.unshift(time+' --- 当前校准[ '+section[0]+' - '+section[1]+' ]');
							this.per=this.$calc.chain(section[1]).divide(this.count).multiply(100).round(2).done();
							this.page++;
							this.poll();
						}
					} else if (result.state == "error") {
						this.list.unshift(time+' --- '+result.info);
					} else {
						this.list.unshift(time+' --- [ ERROR ] 服务器响应超时!');
					}
				});
			},
			help(){
				window.open(this.store.base.webSite);
			}
		}
	};
</script>

<style scoped>
	.summary{
		padding: 3% 0;
	}
	.progress{
		text-align: center;
	}
	.btnGroup{
		text-align: center;
		margin: 16px 0;
	}
	.card{
		width: 80%;
		display: block;
		margin: 0 auto;
	}
	.card >>> .el-card__body{
		height: 36vh;
		overflow: auto;
	}
	.list{
		list-style-type: none;
	}
	.list li{
		margin-bottom: 12px;
		letter-spacing: 1px;
	}
	.list li:last-child{
		margin:0;
	}
</style>
