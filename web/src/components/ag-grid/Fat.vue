<template>
	<div class="fat" tabindex="-1" @keydown.stop.prevent="fatEvent">
		<el-popover popper-class="agGridSelect" ref="popover" trigger="manual">
			<p slot="reference">{{value}}</p>
			<ul class="list">
				<template v-for="(item,index) in list">
					<li @click="choice(item)" :class="{hover:index==hover}">{{item.name}}</li>
				</template>
			</ul>
		</el-popover>
	</div>
</template>
<script>
	//内容组件|资金账户
	import Vue from 'vue';
    export default Vue.extend({
        name: 'Fat',
        data() {
            return {
                value: null,
				list:[],
				hover:0
            }
        },
        created(){
			this.value = this.params.node.data[this.params.dispose.text];
			this.list = this.store.account;
			//匹配索引
			let findIndex=this.list.findIndex(item=>item.name==this.value);
			findIndex==-1||(this.hover=findIndex);
        },
		computed: {
			//读取数据中心
			store() {
				return this.$store.state;
			}
		},
        methods:{
			choice(parm){
				this.value=parm.name;
				this.params.node.data[this.params.dispose.key]=parm.id;
				this.$el.parentNode.focus();
				this.params.api.stopEditing();
			},
			fatEvent(e){
				let keyCode=e.keyCode;
				if(keyCode==13){
					this.choice(this.list[this.hover]);
				}else if(keyCode==27){
					this.$el.parentNode.focus();
					this.params.api.stopEditing();
				}else if(keyCode==38){
					this.list.hasOwnProperty(this.hover-1)&&this.hover--;
				}else if(keyCode==40){
					this.list.hasOwnProperty(this.hover+1)&&this.hover++;
				}
			},
			//返回内容
            getValue(){
				return this.value;
            }
        },
		mounted() {
			this.$nextTick(()=>{
				this.$el.focus();
				this.$refs.popover.showPopper=true;
			});
		}
    })
</script>
<style scoped>
	.fat{
		outline:none;
	}
	.hover{
		background: #f2f2f2;
	}
</style>