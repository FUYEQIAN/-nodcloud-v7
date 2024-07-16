<template>
	<draggable v-if="fields.length>0" :list="fields" :disabled="!drag" @choose="dragChoose" @end="dragEnd" tag="div" class="fieldForm">
		<template v-for="(field,index) in fields">
			<el-form-item :label="field.label" :prop="prefix+field.prop" :rules="field.rules" :key="index">
				<template v-if="field.type == 'text'">
					<el-input type="text" v-model="source[field.model]" :placeholder="field.placeholder"></el-input>
				</template>
				<template v-if="field.type == 'time'">
					<el-date-picker type="date" v-model="source[field.model]" :placeholder="field.placeholder" value-format="yyyy-MM-dd"></el-date-picker>
				</template>
				<template v-if="field.type == 'textarea'">
					<el-input type="textarea" v-model="source[field.model]" :placeholder="field.placeholder"></el-input>
				</template>
				<template v-if="field.type == 'select'">
					<el-select v-model="source[field.model]" :placeholder="field.placeholder" clearable>
						<el-option v-for="option in field.options" :key="option" :label="option" :value="option">
						</el-option>
					</el-select>
				</template>
				<template v-if="field.type == 'checkbox'">
					<el-checkbox-group v-model="source[field.model]">
						<el-checkbox v-for="checkbox in field.checkboxs" :key="checkbox" :label="checkbox"></el-checkbox>
					</el-checkbox-group>
				</template>
				<template v-if="field.type == 'upload'">
					<NodUpload v-model="source[field.model]" :action="$base.web+'service/fieldUpload'"></NodUpload>
				</template>
			</el-form-item>
		</template>
	</draggable>
</template>
<script>
	import draggable from 'vuedraggable';
	import NodUpload from "@/components/lib/NodUpload";
	export default {
		name: "FieldForm",
		components: {
			draggable,
			NodUpload
		},
		props: {
			value:{
				required: true,
				type: Object
			},
			prefix:{
				default:""
			},
			rule:{
				required: true
			},
			drag:{
				default:false
			}
		},
		data() {
			return {
				source:{},
				fields:[]
			}
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			}
		},
		watch:{
			value:{
				handler(val){
					this.$lib.comparison(val,this.source)||this.init();
				},
				deep: true
			},
			source:{
				handler(val){
					this.$emit('input',this.$lib.extend(true,{},this.source));
				},
				deep: true
			}
		},
		created() {
			//字段配置
			if(Array.isArray(this.rule)){
				this.fields=this.rule;
				this.init();
			}else{
				if(this.store.fields.hasOwnProperty(this.rule)){
					this.fields=this.store.fields[this.rule];
					this.init();
				}
			}
		},
		methods: {
			//数据初始化
			init(){
				//1 配置字段和初始值
				let obj = {};
				let replace={};
				for (let field of this.fields) {
					obj[field.model] = field.value;
					//类型验证处理
					if(this.value.hasOwnProperty(field.model) && !this.$lib.VariableEqual(field.value,this.value[field.model])){
						replace[field.model]=field.value;
					}
				}
				//2 覆盖默认值|深拷
				this.source = this.$lib.extend(true,{},obj,this.value,replace);
			},
			//拖动元素选中事件
			dragChoose(obj){
				this.$emit('dragChoose',obj.oldIndex);
			},
			//拖动结束事件
			dragEnd(obj){
				this.$emit('dragEnd',obj.newIndex);
			}
		}
	};
</script>
