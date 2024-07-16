<template>
	<div class="nodEditor">
		<div ref="editor"></div>
	</div>
</template>
<script>
	import E from 'wangeditor';
	export default {
		name: "NodEditor",
		props: {
			value: {
				required: true,
			},
			height:{
				default:360
			},
			placeholder: {
				default: "请输入内容..."
			}
		},
		data() {
			return {
				editor: null,
				html: ""
			}
		},
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			}
		},
		watch: {
			value(data) {
				if (data != this.html) {
					this.editor.txt.html(data);
				}
			}
		},
		methods: {
			init() {
				this.editor = new E(this.$refs.editor);
				
				this.editor.config.height = this.height;
				this.editor.config.placeholder = this.placeholder;
				this.editor.config.onchange=(html)=>{
					this.html = html;
					this.$emit('input', html);
				}
				
				this.editor.config.withCredentials = true;
				this.editor.config.uploadFileName = 'images[]'
				this.editor.config.uploadImgServer = this.$base.web+'service/editorUpload';
				this.editor.config.uploadImgHeaders = {Token: this.store.token};
				this.editor.config.uploadImgHooks = {
				    customInsert: function (insert, result) {
						if(result.state=='success'){
							for (let item of result.info) {
								insert(item);
							}
						}else if(result.state=='error'){
							alert(result.info);
						} else {
							alert('[ ERROR ] 服务器响应超时!');
						}
					}
				}
				
				this.editor.create()
				this.editor.txt.html(this.value)
			},
		},
		mounted() {
			this.init();
		},
		beforeDestroy() {
			//销毁编辑器
			this.editor.destroy();
			this.editor = null;
		}
	}
</script>
