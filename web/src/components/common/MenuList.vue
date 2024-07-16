<template>
	<div class="menuList" :level="level">
		<template v-for="(list, index) in this.menuList">
			<!-- 独立菜单 -->
			<el-menu-item v-if="getMenuType(list)=='routine'" :index="list.key" :key="list.id">
				<i v-if="list.ico != ''" :class="list.ico"></i>
				<span slot="title">{{list.name}}</span>
			</el-menu-item>
			<!-- 附属菜单 -->
			<el-menu-item-group v-else-if="getMenuType(list)=='subsidiary'" class="subsidiaryMenu">
				<el-menu-item :index="list.key" :key="list.id">
					<i v-if="list.ico != ''" :class="list.ico"></i>
					<span slot="title">{{list.name}} </span>
				</el-menu-item>
				<el-menu-item :index="list.sub[0].key" :key="list.sub[0].id">
					<span slot="title">{{list.sub[0].name.split('|')[0]}}</span>
				</el-menu-item>
			</el-menu-item-group>
			<!-- 父子菜单 -->
			<el-submenu v-else :index="list.key" :key="list.id">
				<template slot="title">
					<i v-if="list.ico != ''" :class="list.ico"></i>
					<span slot="title">{{list.name}}</span>
				</template>
				<Menulist :menuList="list.sub" :level="level+1"></Menulist>
			</el-submenu>
		</template>
	</div>
</template>
<script>
	export default {
		name: "Menulist",
		props: {
			menuList:{
				required: true,
				type: Array
			},
			level:{
				default:1
			}
		},
		methods:{
			//检查子菜单类型
			getMenuType(menu){
				if(menu.sub.length==0){
					return 'routine';
				}else if(menu.sub.length==1 && menu.sub[0].type==1){
					return 'subsidiary';
				}else{
					return 'relation';
				}
			}
		}
	};
</script>
