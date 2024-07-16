<template>
	<el-dropdown class="nodUpload"  split-button placement="bottom-start">
		<el-upload
			:action="action"
			:headers="{Token:$store.state.token}"
			:show-file-list="false"
			:on-success="uploadSuccess"
		>
			<i class="text el-icon-upload"> 点击上传</i>
		</el-upload>
		<el-dropdown-menu slot="dropdown">
			<template v-if="files.length>0">
				<template v-for="(file,index) in files">
					<li>
						<el-row>
							<el-col :span="22">
								<p class="file" @click="preview(file)">{{file.name}}</p>
							</el-col>
							<el-col :span="2">
								<i class="el-icon-delete" @click="remove(index)"></i>
							</el-col>
						</el-row>
					</li>
				</template>
			</template>
			<template v-else>
				<li class="empty">暂无文件信息</li>
			</template>
		</el-dropdown-menu>
	</el-dropdown>
</template>
<script>
	export default {
		name: "NodUpload",
		props: {
			value:{
				required: true,
				type:Array
			},
			action:{
				required: true
			}
		},
		data() {
			return {
				files:[]
			}
		},
		created(){
			this.files=[].concat(this.value);
		},
		watch: {
			value:{
				handler(val){
					if(!this.$lib.comparison(val,this.files)){
						this.files=[].concat(val);
					}
				},
				deep: true
			}
		},
		methods: {
			//上传成功
			uploadSuccess(result, file) {
				if (result.state == 'success') {
					this.files.push({
						name: file.name,
						url: result.info
					});
					this.incident();
					this.$message({
						type: "success",
						message: "[ " + file.name + " ] 上传成功!"
					});
				} else if (result.state == "error") {
					this.$message({
						type: "warning",
						message: "[ " + file.name + " ]" + result.info
					});
				} else {
					this.$message({
						type: "error",
						message: "[ ERROR ] 服务器响应超时!"
					});
				}
			},
			//删除文件
			remove(index) {
				this.files.splice(index,1);
				this.incident();
			},
			//提交事件
			incident(){
				this.$emit('input',this.files);
				this.$emit('change',this.files);
			},
			//下载文件
			preview(file){
				this.$message({type: "success",message: "[ " + file.name + " ] 下载请求中..."});
				setTimeout(()=>{window.open(file.url)},1000);
			},
		}
	};
</script>
<style scoped>
	.nodUpload >>> .text{
		width: 142px;
		color: #606266;
	}
	.el-dropdown-menu{
		width: 202px;
		padding: 6px 12px;
		list-style-type:none;
		box-sizing: border-box;
	}
	.el-dropdown-menu li{
		color: #606266;
		font-size: 12px;
		cursor: pointer;
		line-height: 26px;
		border-bottom: 1px solid #ccc;
	}
	.el-dropdown-menu li:last-of-type{
		border-bottom:none;
	}
	.el-dropdown-menu .file{
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}
	.empty{
		text-align: center;
	}
</style>