<template>
    <div class="setter">
		<i v-if="hasData" class="el-icon-delete" @click="delRow"></i>
		<i v-else class="el-icon-document" @click="setRow"></i>
	</div>
</template>

<script>
	//内容组件|操作组件
	import Vue from 'vue';
    export default Vue.extend({
        name: 'Setter',
		computed: {
			hasData(){
				return this.params.node.data.key!=null?true:false;
			}
		},
		methods:{
			//图标事件
			setRow(){
				this.$parent.$emit('setter',this.params.api.getFocusedCell());
			},
			//删除数据
			delRow(){
				this.params.api.applyTransaction({remove:[this.params.data]});
				this.params.context.runHandleGrid();
			}
		}
    })
</script>