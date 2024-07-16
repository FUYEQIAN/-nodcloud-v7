<template>
    <input class="ag-cell-edit-input" type="text" v-model="value" ref="input" @keydown.enter="quickEnter">
</template>
<script>
	//内容组件|扫码组件
	import Vue from 'vue';
    export default Vue.extend({
        name: 'QuickInput',
        data() {
            return {
                value: ''
            }
        },
		computed: {
			hasKey(){
				return this.params.node.data.key==null?false:true;
			}
		},
        methods:{
			//返回内容
            getValue(){
				return '';
            },
			//回车事件
			quickEnter(){
				this.$parent.$emit('quickEnter',this.value,this.params.rowIndex);
			},
			//编辑状态
			isCancelBeforeStart() {
				return this.hasKey;
			},
        },
		mounted() {
			if(!this.hasKey){
				this.$nextTick(()=>{
					this.$refs.input.focus();
				});
			}
		}
    })
</script>