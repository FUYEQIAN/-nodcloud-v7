<template>
    <div class="handle">
		<i v-if="firstRow" class="el-icon-circle-plus-outline" @click="addRow"></i>
		<i v-else class="el-icon-delete" @click="delRow"></i>
	</div>
</template>

<script>
	//内容组件|操作组件
	import Vue from 'vue';
    export default Vue.extend({
        name: 'Handle',
		computed: {
			firstRow(){
				return this.params.node.firstChild;
			}
		},
		methods:{
			//图标事件
			addRow(){
				this.$parent.$emit('addRow',this.params.api.getFocusedCell());
			},
			//删除数据
			delRow(){
				this.params.api.applyTransaction({remove:[this.params.data]});
				this.params.context.runHandleGrid();
			}
		}
    })
</script>