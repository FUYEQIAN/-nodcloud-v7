<template>
	<el-input :class="['logistics',{moreIco:more.length>0}]" v-model="number" @input="inputChange" :placeholder="placeholder">
		<template slot="prefix">
			<el-dropdown placement="top" trigger="click" @command="command">
				<span class="el-dropdown-link">
					<el-tooltip placement="bottom" effect="light">
						<div slot="content">{{mold.name}}</div>
						<i class="el-icon-truck"></i>
					</el-tooltip>
				</span>
				<el-dropdown-menu slot="dropdown">
					<template v-for="list in lists">
						<el-dropdown-item :command="list.key">{{list.name}}</el-dropdown-item>
					</template>
				</el-dropdown-menu>
			</el-dropdown>
		</template>
		<template slot="suffix">
			<div class="icoGroup">
				<i class="el-icon-link" @click="openLink"></i>
				<template v-if="more.length>0">
					<el-divider direction="vertical"></el-divider>
					<el-popover placement="top" trigger="hover">
						<ul class="moreInfo">
							<template v-for="item in more">
								<li>{{item}}</li>
							</template>
						</ul>
						<i class="el-icon-arrow-down" slot="reference"></i>
					</el-popover>
				</template>
			</div>
		</template>
	</el-input>
</template>
<script>
	export default {
		name: "Logistics",
		props: {
			// {key:auto,name:'自动识别',number:'000000'}
			value:{
				required: true,
				type: Object
			},
			placeholder: {
				default: "请输入物流单号"
			},
			more:{
				type: Array,
				default:()=>{
					return [];
				}
			}
		},
		data() {
			return {
				mold:{
					key:'auto',
					name:'自动识别'					
				},
				lists:[],
				number:""
			}
		},
		created(){
			this.init();
			this.handle();
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			},
		},
		watch: {
			value:{
				handler(val){
					this.$lib.comparison(val,{key:this.mold.key,key:this.mold.name,number:this.number})||this.handle();
				},
				deep:true
			}
		},
		methods: {
			//初始化
			init(){
				//初始数据
				this.store.sys.logistics.forEach(item=>{
					item.enable&&this.lists.push(item);
				})
			},
			//数据处理
			handle(){
				let obj=Object.assign({},this.value);
				this.number=obj.number;
				this.mold.key=obj.key;
				this.mold.name=obj.name;
			},
			//物流改变
			command(key){
				let find=this.lists.find(item=>item.key==key);
				this.mold.key=find.key;
				this.mold.name=find.name;
				this.$emit('input',{key:find.key,name:find.name,number:this.number});
			},
			//单号改变
			inputChange(val){
				this.$emit('input',{key:this.mold.key,name:this.mold.name,number:val});
			},
			//物流查询
			openLink(){
				if(this.$lib.validate('empty',this.number)){
					this.$message({type: "warning",message:"物流单号不可为空!"});
				}else{
					let parm={nu:this.number};
					this.mold.key=='auto'||(parm.com=this.mold.key);
					window.open('https://www.kuaidi100.com/chaxun?'+this.$lib.objToParm(parm))
				}
			}
		}
	};
</script>
<style scoped>
	.el-dropdown-menu{
		max-height: 220px;
		overflow: auto;
	}
	.el-dropdown-menu >>> .popper__arrow{
		display: none;
	}
	.moreIco >>> .el-input__inner{
		padding-left: 22px;
		padding-right: 50px;
	}
	.moreInfo{
		list-style:none;
	}
	.moreInfo li{
		font-size: 12px;
		line-height: 20px;
	}
</style>
