<template>
	<el-popover class="nodTree" ref="nodTree">
		<el-input v-model="label" :placeholder="placeholder" slot="reference" @keydown.native="inputEvent" readonly>
			<template slot="suffix">
				<i :class="['el-input__icon',label!=''?'el-icon-circle-close':'el-icon-arrow-down']" @click.stop="delModel"></i>
			</template>
		</el-input>
		<el-tree
			ref="tree"
			@check="setTree"
			@node-click="setTree"
			:data="treeData"
			:props="treeProps"
			:node-key="treeKey"
			:current-node-key="checked"
			:show-checkbox="checkbox"
			:check-strictly="checkStrictly"
			:default-checked-keys="checkeds"
			:default-expanded-keys="expanded"
			:highlight-current="true"
		></el-tree>
		
	</el-popover>
</template>
<script>
	export default {
		name: "NodTree",
		props: {
			value:{
				required: true
			},
			treeData:{
				required: true,
				type:Array
			},
			treeKey:{
				default:'id'
			},
			treeProps:{
				default:()=>{
					return {label:'name',children:'sub'};
				}
			},
			placeholder:{
				default:"请点击选择数据"
			},
			checkStrictly:{
				default:false
			}
		},
		data() {
			return {
				label:'',
				checked:null,
				checkeds:[],
				expanded:[],
				checkbox:false
			}
		},
		created() {
			this.init();
		},
		computed: {
			//读取数据中心
			flatTree() {
				let flat = this.$lib.flatten(this.treeData);//扁平化数据
				return flat;
			}
		},
		watch: {
			value:{
				handler(val){
					if(this.checkbox){
						if(this.$lib.comparison(val,this.checkeds)){
							this.checkeds=val;
							this.$refs.tree.setCheckedKeys(val,true);
						}
					}else{
						if(val!=this.checked){
							this.checked=val;
							this.$refs.tree.setCurrentKey(val);
						}
					}
				},
				deep: true
			},
			checked(){
				this.setInput();
			},
			checkeds:{
				handler(){
					this.setInput();
				},
				deep: true
			}
		},
		methods: {
			//初始化
			init(){
				if(this.value!=null){
					//数据模式
					if(Array.isArray(this.value)){
						this.checkbox=true;
						this.checkeds=this.value;
					}else{
						this.checkbox=false;
						this.checked=this.value;
					}
					//设置展开项
					for (let item of this.checkbox?this.checkeds:[this.checked]) {
						let find=this.flatTree.find(obj=>obj.id==item);//匹配数据
						(find!=undefined&&find.hasOwnProperty('pid'))&&this.expanded.push(find.pid);
					}
				}
				
			},
			//设置文本信息
			setInput(){
				let text=[];
				for (let item of this.checkbox?this.checkeds:[this.checked]) {
					let find=this.flatTree.find(obj=>obj.id==item);//匹配数据
					find==undefined||text.push(find.name);
				}
				this.label=text.join(',');//赋值数据
			},
			//设置树数据
			setTree(data) {
				if(this.checkbox){
					this.checkeds=this.$refs.tree.getCheckedKeys();
					this.$emit('input',this.checkeds);
				}else{
					this.checked=data.id;
					this.$emit('input',this.checked);
					this.$refs['nodTree'].showPopper=false;
				}
			},
			//删除绑定数据
			delModel(){
				this.label='';
				if(this.checkbox){
					this.checkeds=[];
					this.$refs.tree.setCheckedKeys([]);
				}else{
					this.checked=null;
					this.$refs.tree.setCurrentKey(null);
				}
				this.$emit('input',this.checkbox?[]:null);
				this.$refs['nodTree'].showPopper=false;
			},
			//输入框-键盘事件
			inputEvent(e){
				//ESC优化
				if(this.$refs['nodTree'].showPopper && e.keyCode==27){
					e.stopPropagation();
				}
			}
		}
	};
</script>
<style scoped>
	.nodTree{
		display: inline-block;
	}
	.el-tree{
		width: 193px;
	}
</style>