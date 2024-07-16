<template>
	<el-popover class="nodList" ref="nodList" :disabled="disabled">
		<template slot="reference">
			<el-input v-model="label" ref="label" :placeholder="placeholder" tabindex="-1" @keydown.native="inputEvent" :disabled="disabled" readonly>
				<template slot="suffix">
					<i :class="['el-input__icon',label==''?'el-icon-arrow-down':'el-icon-circle-close']" @click.stop="delModel"></i>
				</template>
			</el-input>
		</template>
		<div class="selectBox">
			<el-input
				v-model="query"
				ref="search"
				@keydown.native.stop="searchEvent"
				placeholder="F1 输入内容回车搜索"
				prefix-icon="el-icon-search"
				size="mini"
				tagkey="search"
			></el-input>
			<template v-if="optionList.length>0">
				<ul class="list">
					<template v-for="(option,index) in optionList">
						<li :class="[{selected:option.id==value},{foucs:index==foucs}]" @click="setOption(option)">{{option.name}}</li>
					</template>
				</ul>
			</template>
			<template v-else>
				<p class="emptyList">未查询到数据</p>
			</template>
			<div class="operate">
				<el-pagination
					:current-page.sync="page.current"
					:total="page.total"
					:page-size.sync="page.size"
					@current-change="record(0)"
					layout="prev,next,total"
					background
				>
				</el-pagination>
			</div>
		</div>
	</el-popover>
</template>
<script>
	export default {
		name: "NodList",
		props: {
			value:{
				required: true
			},
			action:{
				required: true
			},
			scene:{
				required: true
			},
			parm:{
				default:()=>{
					return {};
				}
			},
			disabled:{
				default:false,
			},
			placeholder:{
				default:"请点击选择数据"
			}
		},
		data() {
			return {
				id:null,
				label:"",
				query:"",
				optionList:[],
				foucs:0,
				page:{
					current:1,
					total:0,
					size:6
				}
			}
		},
		created(){
			this.record(1);//查询列表数据
			this.get();//查询指定数据
		},
		watch: {
			value(val,old){
				val==old||this.get();
			}
		},
		methods: {
			//初始化组件
			init(){
				this.id=null,
				this.label="";
				this.query="";
				this.page={current:1,total:0,size:6};
				this.record(1);//查询列表数据
				this.$emit('input',null);
				this.$emit('change',null);
			},
			//查询列表数据
			record(page){
				page==0||(this.page.current=page);
				let parm = Object.assign({},this.parm,{
					page:this.page.current,
					limit:this.page.size,
					query:this.query
				});
				this.$axios.post(this.action,parm).then(result => {
					if (result.state == "success") {
						this.optionList = result.info;
						this.page.total = result.count;
						//激活键盘事件
						if(this.optionList.length>0 && document.activeElement.getAttribute('tagkey')=='search'){
							this.foucs=0;
							this.$refs.label.focus();
						}
					} else if (result.state == "error") {
						this.$message({type: "warning",message: result.info});
					} else {
						this.$message({type: "error",message: "[ ERROR ] 服务器响应超时!"});
					}
				});
			},
			//查询指定数据
			get(){
				if(this.value!=null && !this.$lib.validate('empty',this.value)){
					let find=this.optionList.find(option=>option.id==this.value);
					if(find==undefined){
						this.$axios.post('service/getScene',{
							id:this.value,
							scene:this.scene
						}).then(result => {
							if (result.state == "success") {
								if(result.info==null){
									this.$message({type: "warning",message: "[ NodList ]指定数据查询结果为空!"});
								}else{
									this.setOption(result.info);
								}
							} else if (result.state == "error") {
								this.$message({type: "warning",message: result.info});
							} else {
								this.$message({type: "error",message: "[ ERROR ] 服务器响应超时!"});
							}
						});
					}else{
						this.setOption(find);
					}
				}
			},
			//设置选中
			setOption(option){
				if(option.id!=this.id){
					this.id=option.id;
					this.label=option.name;
					this.$emit('input',option.id);
					this.$emit('change',option);
				}
				this.$refs['nodList'].showPopper=false;
			},
			//删除绑定数据
			delModel(){
				this.id=null;
				this.label="";
				this.$emit('input',null);
				this.$emit('change',null);
				this.$refs['nodList'].showPopper=false;
			},
			//组件-切换显示
			switchState(){
				this.$refs.label.focus();
				this.$refs['nodList'].showPopper=!this.$refs['nodList'].showPopper;
			},
			//输入框-键盘事件
			inputEvent(e){
				let keyCode=e.keyCode;
				if(this.$refs['nodList'].showPopper){
					if(keyCode==13){
						//回车
						this.setOption(this.optionList[this.foucs]);
					} else if(keyCode==27){
						//ESC
						e.stopPropagation();
					} else if(keyCode==38){
						//上
						this.optionList.hasOwnProperty(this.foucs-1)&&this.foucs--;
					} else if(keyCode==40){
						//下
						this.optionList.hasOwnProperty(this.foucs+1)&&this.foucs++;
					} else if(keyCode==112){
						//F1
						this.$refs.search.focus();
						e.preventDefault();
						e.stopPropagation();
					}
				}else{
					if(keyCode==13){
						//回车
						this.$refs['nodList'].showPopper=true;
					}
				}
				//DEL
				keyCode==46&&this.delModel();
			},
			//搜索框-键盘事件
			searchEvent(e){
				let keyCode=e.keyCode;
				if(keyCode==13){
					//回车
					this.record(1);
				} else if(keyCode==27){
					//ESC
					this.$refs.label.focus();
					this.$refs['nodList'].showPopper=false;
				}else if(keyCode==112){
					//F1
					e.preventDefault();
				}
			}
		}
	};
</script>
<style scoped>
	.nodList{
		display: inline-block;
	}
	.selectBox .list{
		padding: 6px 0;
		list-style: none;
		box-sizing: border-box;
	}
	.selectBox .list li{
		color: #606266;
		height: 34px;
		cursor: pointer;
		padding: 0 20px;
		overflow: hidden;
		line-height: 34px;
		white-space: nowrap;
		box-sizing: border-box;
		text-overflow: ellipsis;
	}
	.selectBox .list .selected{
		color: #409eff;
	}
	.selectBox .list .foucs{
		background: #f8f8f8;
	}
	.selectBox .list li:hover{
		background: #f5f7fa;
	}
	.emptyList{
		margin: 6px 0;
		font-size: 14px;
		line-height: 28px;
		text-align: center;
		background: #f2f2f2;
		letter-spacing: 1px;
	}
	.operate{
		padding-top: 6px;
		border-top: 1px solid #dcdfe6;
	}
</style>