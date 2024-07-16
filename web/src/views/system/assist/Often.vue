<template>
	<div class="often area">
		<div class="layout">
			<el-button-group>
				<el-button @click="save">保存</el-button>
				<el-button @click="reload">刷新</el-button>
			</el-button-group>
		</div>
		<el-divider></el-divider>
		<ul class="list">
			<template v-for="item of this.list">
				<li>
					<span>{{item.name}}</span>
					<div class="items">
						<template v-for="items of item.items">
							<el-checkbox v-model="check[items.key]" :label="items.name" border></el-checkbox>
						</template>
					</div>
				</li>
			</template>
			
		</ul>
	</div>
</template>
<script>
	export default {
		name: "Often",
		data() {
			return {
				list:[],
				check:{}
			};
		},
		created() {
			this.init();
			this.record(); //获取数据
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			}
		},
		methods: {
			//获取数据
			init(){
				for (let row of this.store.menu) {
					let item={name:row.name,items:[]};
					let flatten = this.$lib.flatten([row]);
					for (let f of flatten) {
						if(f.type==1 || (f.sub.length==1 && f.sub[0].type==1) || f.sub.length==0){
							let name=f.name.split('|');
							item.items.push({key:f.key,name:name.length==1?name[0]:name[1]});
							this.$set(this.check,f.key,false);
						}
					}
					this.list.push(item);
				}
			},
			//获取数据
			record() {
				this.$axios.post("often/record").then(result => {
					if (result.state == "success") {
						for (let info of result.info) {
							this.$set(this.check,info.key,true);
						}
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
			//保存数据
			save() {
				let parm=[];
				let flatten = this.$lib.flatten(this.store.menu);
				for (let s in this.check) {
					if(this.check[s]){
						let obj=flatten.find(i=>i.key==s);
						let name=obj.name.split('|');
						parm.push({name:name.length==1?name[0]:name[1],key:obj.key});
					}
				}
				this.$axios.post("often/save",{parm:parm}).then(result => {
					if (result.state == "success") {
						this.$message({
							type: "success",
							message: "保存成功!"
						});
						this.$lib.updateStore(this);
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
			//页面刷新
			reload() {
				this.$bus.emit('homeReload',this.$options.name);
				this.$message({
					type: "success",
					message: "页面刷新成功!"
				});
			}
		}
	};
</script>

<style scoped>
	.layout {
		display: flex;
		justify-content: flex-end;
	}
	ul,ol{
		list-style:none
	}
	.list li{
		margin-bottom: 12px;
		position: relative;
	}
	.list li>span{
		margin-right: 12px;
		position: absolute;
		width: 70px;
		border-right: 1px solid #DCDFE6;
		height: calc( 100% - 12px);
		letter-spacing: 1px;
	}
	.items{
		margin-left: 80px;
	}
	.el-checkbox{
		height: auto !important;
		margin-right: 12px;
		margin-left: 0 !important;
		margin-bottom: 12px;
	}
</style>
