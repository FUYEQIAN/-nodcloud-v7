<template>
	<div class="company" tabindex="-1" @keydown.stop.prevent="companyEvent">
		<el-popover popper-class="agGridSelect" ref="popover" trigger="manual">
			<p slot="reference">{{value}}</p>
			<ul class="list">
				<template v-for="(item,index) in list">
					<li @click="choice(item.name)" :class="{hover:index==hover}">{{item.name}}</li>
				</template>
			</ul>
		</el-popover>
	</div>
</template>
<script>
	//内容组件|多单位
	import Vue from 'vue';
    export default Vue.extend({
        name: 'Company',
        data() {
            return {
                value: null,
				list:[],
				hover:0
            }
        },
        created(){
			if(this.hasData){
				this.value = this.params.value;
				//构造数据
				let unitData=this.params.node.data.unitData;
				this.list=[{name:unitData[0].source}].concat(unitData);
				//匹配索引
				let findIndex=this.list.findIndex(item=>item.name==this.value);
				findIndex==-1||(this.hover=findIndex);
			}
        },
		computed: {
			hasData(){
				let data=this.params.node.data;
				if(data.key==null){
					return false;
				}else{
					if(data.unitData.length>0){
						return true;
					}else{
						return false;
					}
				}
			}
		},
        methods:{
			choice(value){
				this.value=value;
				let data=this.params.node.data;
				let relation = this.$lib.unitRelation(this.value,data.unitData,this);
				let newPrice=this.$calc.chain(data.unitRelation.valence).multiply(relation.multiple).multiply(relation.discount).round(this.$store.state.sys.fun.digit.money).done();
				data.unitRelation.multiple=relation.multiple;
				this.$grid.updateGridData(this.params,data.uniqid,'price',newPrice);
				if(data.unitData.length>0 && data.hasOwnProperty('serial') && data.serial.length>0){
					this.$grid.updateGridData(this.params,data.uniqid,'nums',this.$calc.chain(data.serial.length).divide(relation.multiple).done());
				}
				this.$el.parentNode.focus();
				this.params.api.stopEditing();
			},
			companyEvent(e){
				let keyCode=e.keyCode;
				if(keyCode==13){
					this.choice(this.list[this.hover].name);
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
            },
			//编辑状态
			isCancelBeforeStart() {
				return !this.hasData;
			}
        },
		mounted() {
			if(this.hasData){
				this.$nextTick(()=>{
					this.$el.focus();
					this.$refs.popover.showPopper=true;
				});
			}
		}
    })
</script>
<style scoped>
	.company{
		outline:none;
	}
	.hover{
		background: #f2f2f2;
	}
</style>