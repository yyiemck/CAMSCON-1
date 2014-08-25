@extends('admin.layouts.admin-master')

@section('head_title')
User groups
@stop

@section('head_styles')
<style type="text/css">
	.left-col .btn {
		width:100%;
	}

	.group-list {
		list-style-type: none;
		padding: 0px;
		margin: 0px;
	}

	.group-list li {
		border:1px solid #00ADA9;
		margin-bottom:5px;
		padding:8px 10px;
	}

	.group-list .badge {
		background-color: #00ADA9;
	}

	.left-col .all-users, 
	.left-col .search-box {
		margin-bottom:10px;
	}

	.left-col .search-box {
		border:1px solid #ddd;
		padding:15px 30px;
	}

	.right-col .action-controls {
		color: #888;
		font-size: 16px;
		font-style: italic;
		margin: 20px 0px;
	}

	.right-col .action-controls .group-mod-control {
		display: inline-table;
		vertical-align: bottom;
		width:300px;
	}

	#selectAllUsers {
		cursor:pointer;
	}
</style>
@stop

@section('content')
<!--Left col-->
<div class="left-col col-sm-3">
	<div class="all-users">
		<a href="{{action('GroupsController@showUsers')}}" class="btn btn-primary">사용자 전체보기 <span class="badge">{{$userCount['all']}}</span></a>
	</div>

	<div class="search-box">
		{{ Form::open( array('url'=>action('GroupsController@showUsers', array('search')), 'method'=>'GET') ) }}
		<label>사용자 검색</label>
		<div class="form-group">
			<select class="form-control" name="field">
				<option value="email">이메일</option>
				<option value="nickname">닉네임</option>
			</select>
		</div>
		<div class="form-group">
			<input type="text" class="form-control" name="query" placeholder="검색어" />
		</div>
		<button type="submit" class="btn btn-primary">검색</button>
		{{ Form::close() }}
	</div>

	<div class="user-groups">
		<ul class="group-list">
		@foreach($groups as $group)
		<li><a href="{{action('GroupsController@showUsers', array('group', $group->id))}}">{{$group->name}}</a> <span class="badge">{{$userCount[$group->id]}}</span></li>
		@endforeach
		</ul>
	</div>
</div>
<!--//Left col-->

<!--Right col-->
<div class="right-col col-xs-9">
	<h3>{{{$queryDescription}}}</h3>

	<div class="action-controls">
		선택한 사용자들을
		<button type="button" id="deleteCheckedBtn" class="btn btn-danger">삭제</button> 
		또는
		<div class="group-mod-control input-group">
			<select id="groupSelector" class="form-control">
				@foreach($groups as $group)
				<option value="{{$group->id}}">{{{$group->name}}}</option>
				@endforeach
			</select>
			<div class="input-group-btn">
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">그룹으로 <span class="caret"></span></button>
				<ul class="dropdown-menu dropdown-menu-right" role="menu">
					<li><a href="#" id="copyCheckedBtn">복사</a></li>
					<li><a href="#" id="moveCheckedBtn">이동</a></li>
				</ul>
			</div><!-- /btn-group -->
		</div><!-- /input-group -->
	</div>

	@if(Session::has('action_success'))
	<div class="alert alert-success alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
		<strong>성공</strong> {{Session::get('action_success')}}
	</div>
	@elseif(Session::has('action_error'))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
		<strong>오류</strong> {{Session::get('action_error')}}
	</div>
	@endif

	@if(count($users)>0)
	<table id="usersTable" class="table">
		<thead>
			<tr>
				<th><span id="selectAllUsers">선택</span></th>
				<th>#</th>
				<th>닉네임</th>
				<th>이메일</th>
				<th>성별</th>
				<th>가입 일자</th>
			</tr>
		</thead>
		<tbody>
		@foreach($users as $user)
			<tr><td><input type="checkbox" name="selected_users[]" value="{{$user->id}}" /></td><td>{{$user->id}}</td><td>{{{$user->nickname}}}</td><td>{{$user->email}}</td><td>{{$user->gender}}</td><td>{{$user->created_at}}</td></tr>
		@endforeach
		</tbody>
	</table>
	@else
	<div class="alert alert-warning">
		조회된 사용자가 없습니다! :(
	</div>
	@endif

	@if(is_object($users))
	{{$users->links()}}
	@endif
</div>
<!--//Right col-->

{{ Form::open( array('id'=>'userActionForm', 'method'=>'POST') ) }}
	<input type="hidden" name="checked" />
	<input type="hidden" name="group_id" />
	<input type="hidden" name="current_group" value="{{$currentGroup or ''}}" />
{{ Form::close() }}
@stop

@section('footer_scripts')
<script type="text/javascript">
var UserActionsController={
	table:null,
	btns:{
		checkAll:null,
		deleteChecked:null,
		copyChecked:null,
		moveChecked:null
	},
	endpoints:{
		delete:"{{action('GroupsController@deleteUsers')}}",
		copy:"{{action('GroupsController@copyUsers')}}",
		move:"{{action('GroupsController@moveUsers')}}"
	},
	groupSelector:null,
	form:null,
	init:function() {
		scope=this;

		this.table=$('#usersTable');
		this.groupSelector=$('#groupSelector');
		this.btns.checkAll=$('#selectAllUsers');
		this.btns.deleteChecked=$('#deleteCheckedBtn');
		this.btns.copyChecked=$('#copyCheckedBtn');
		this.btns.moveChecked=$('#moveCheckedBtn');
		this.form=$('#userActionForm');

		this.btns.checkAll.click(function() {
			scope.checkAll();
		});
		this.btns.deleteChecked.click(function() {
			AdminMaster.confirmModal.launch('진심으로 선택한 사용자들을 삭제하시겠습니까?', function() {
				UserActionsController.deleteChecked();
			}, null);
		});
		this.btns.copyChecked.click(function(e) {
			e.preventDefault();
			scope.copyChecked();
		});
		this.btns.moveChecked.click(function(e) {
			e.preventDefault();
			scope.moveChecked();
		})
	}/*init()*/,
	checkAll:function() {
		$('#usersTable').find('input[type="checkbox"]').each(function() {
			if($(this).prop('checked')===true) {
				$(this).prop('checked',false);
			} else {
				$(this).prop('checked',true);
			}
		});
	}/*checkAll()*/,
	deleteChecked:function() {
		var checked=this.get_checked();
		if(checked.length>0) {
			this.form.find('input[name="checked"]').val(checked);
			this.form.prop('action', this.endpoints.delete);
			this.form.submit();
		} else {
			this.checked_null_msg();
		}
	}/*deleteChecked()*/,
	copyChecked:function() {
		var checked=this.get_checked();
		if(checked.length>0) {
			this.form.find('input[name="checked"]').val(checked);
			this.form.find('input[name="group_id"]').val(this.groupSelector.val());
			this.form.prop('action', this.endpoints.copy);
			this.form.submit();
		} else {
			this.checked_null_msg();
		}
	}/*copyChecked()*/,
	moveChecked:function() {
		var checked=this.get_checked();
		if(checked.length>0) {
			this.form.find('input[name="checked"]').val(checked);
			this.form.find('input[name="group_id"]').val(this.groupSelector.val());
			this.form.prop('action', this.endpoints.move);
			this.form.submit();
		} else {
			this.checked_null_msg();
		}
	}/*moveChecked()*/,
	get_checked:function() {
		var checked=[];
		this.table.find('input[type="checkbox"]').each(function() {
			if($(this).prop('checked')===true) {
				checked.push($(this).val());
			}
		});
		return checked;
	}/*get_checked()*/,
	checked_null_msg:function() {
		AdminMaster.alertModal.launch('선택된 사용자가 없습니다! :(',null,null);
	}/*checked_null_msg()*/
};//UserActionsController{}

$(document).ready(function() {
	UserActionsController.init();
});//document.ready()
</script>
@stop