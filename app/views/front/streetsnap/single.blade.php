@extends('front.layouts.master')

@section('head_styles')
<!--FB Open Graph tags-->
@if($snap->affiliation)
<meta property="og:title" content="{{{$snap->meta->name}}} {{{$snap->affiliation}}} {{{$snap->name}}}" />
@else
<meta property="og:title" content="{{{$snap->meta->name}}} {{{$snap->name}}}" />
@endif
<meta property="og:site_name" content="CAMSCON" />
<meta property="og:url" content="{{$snap->single_url}}" />
@if(empty($snap->photographer_comment))
<meta property="og:description" content="Uploaded at CAMSCON.kr" />
@else
<meta property="og:description" content="<?php echo(strip_tags(preg_replace("/\r\n|\r|\n/", ' ', $snap->photographer_comment))); ?>" />
@endif
<meta property="og:image" content="{{$snap->primary->url}}" />
<meta property="fb:app_id" content="562009567255774" />
<meta property="og:locale" content="ko_KR" />

<!--Single View styles-->
<link href="{{asset('front-assets/single-view/single.6327e3e1ec.css')}}" rel="stylesheet" />
@stop

@section('content')
<div class="breadcrumbs">
	@foreach($breadcrumbs as $key=>$breadcrumb)
		@if($key>0)
		<span class="caret-right"></span>
		@endif
		<a href="{{$breadcrumb['url']}}">{{{$breadcrumb['name']}}}</a>
	@endforeach
</div>

<div class="single-container row">
	<div id="photoCol" class="photo-col col-xs-12 col-sm-7">
		<nav class="content-nav clearfix">
			@if($nextSnap)
			<a href="{{action('StreetSnapController@getSingle', array('category'=>$category, 'slug'=>$slug, 'id'=>$nextSnap->id))}}" alt="" class="next" style="background-image:url('{{asset('front-assets/layouts/content_nav_next.png')}}');">Next</a>
			@endif

			@if($prevSnap)
			<a href="{{action('StreetSnapController@getSingle', array('category'=>$category, 'slug'=>$slug, 'id'=>$prevSnap->id))}}" alt="" class="prev" style="background-image:url('{{asset('front-assets/layouts/content_nav_prev.png')}}');">Prev</a>
			@endif
		</nav>

		<figure id="snapPrimary" class="primary-photo pinned">
			@if($snap->liked->count()>0)
			<div class="like-btn liked" data-type="StreetSnap" data-id="{{$snap->id}}">
			@else
			<div class="like-btn" data-type="StreetSnap" data-id="{{$snap->id}}">
			@endif
				<span class="total-likes" data-target-type="StreetSnap" data-target-id="{{$snap->id}}">{{$snap->cached_total_likes}}</span>
				<span class="caption"></span>
			</div><!--/.like-btn-->
			<button type="button" class="fb-share-btn" data-url="{{$snap->single_url}}"></button>
			<div class="pin-container"></div>
			<img src="{{$snap->primary->url}}" alt="" width="{{$snap->primary->width}}" height="{{$snap->primary->height}}" />
		</figure><!--/.primary-photo-->

		@foreach($snap->attachments as $attachment)
		<figure>
			<img src="{{$attachment->url}}" alt="" width="{{$attachment->width}}" height="{{$attachment->height}}" />
		</figure>
		@endforeach
	</div><!--/#photoCol-->
	<div id="dataCol" class="data-col col-xs-12 col-sm-5">
		<div class="icon-section">
			<h3 class="name">{{{$snap->name}}}</h3>
			@if($snap->meta_type=='BlogMeta')
			<h3 class="category">{{{$snap->meta->name}}} / {{$snap->meta->country}} @if(Auth::check() && Auth::user()->is_staff){{'<a href="'.action('StreetSnapEditController@showEditor', $snap->id).'" class="btn btn-primary btn-xs">Edit</a>'}}@endif</h3>
			@elseif($snap->affiliation)
			<h3 class="category">{{{$snap->meta->name}}} / {{{$snap->affiliation}}} @if(Auth::check() && Auth::user()->is_staff){{'<a href="'.action('StreetSnapEditController@showEditor', $snap->id).'" class="btn btn-primary btn-xs">Edit</a>'}}@endif</h3>
			@else
			<h3 class="category">{{{preg_replace('/Meta$/', '', $snap->meta_type)}}} / {{{$snap->meta->name}}} @if(Auth::check() && Auth::user()->is_staff){{'<a href="'.action('StreetSnapEditController@showEditor', $snap->id).'" class="btn btn-primary btn-xs">Edit</a>'}}@endif</h3>
			@endif
		</div>

		@if($snap->pins->count())
		<div class="pins-section">
			<ul id="pinList" class="pin-list"></ul>
		</div>
		@endif

		<div class="notes-section">
			@if(!empty($snap->subject_comment))
			@if($snap->gender=='female')
			<h4>She says:</h4>
			@else
			<h4>He says:</h4>
			@endif
			<div class="icon-comment">
				{{autop($snap->subject_comment)}}
			</div>
			@endif

			@if(!empty($snap->photographer_comment))
			<h4>Inspirer's note:</h4>
			<div class="photographers-note">
				{{autop($snap->photographer_comment)}}
			</div>
			@endif
		</div><!--/.notes-section-->

		<div class="photographer-section">
			@if($snap->user->profileImage)
			<img src="{{$snap->user->profileImage->url}}" alt="" class="profile-img" />
			@else
			<img src="{{asset('front-assets/profile/profile_default_big.png')}}" alt="" class="profile-img" />
			@endif
			<div class="profile-data">
				<strong class="name">{{{$snap->user->nickname}}}</strong>
				@if(!empty($snap->user->slug))
				<p>MY PAGE <a href="{{action('ProfileController@showProfile', $snap->user->slug)}}">{{action('ProfileController@showProfile', $snap->user->slug)}}</a></p>
				@else
				<p>MY PAGE <a href="{{action('ProfileController@showProfile', $snap->user->id)}}">{{action('ProfileController@showProfile', $snap->user->id)}}</a></p>
				@endif
				<p>Blog @if(!empty($snap->user->blog))<a href="{{$snap->user->blog}}" target="_blank">{{$snap->user->blog}}</a>@else{{'-'}}@endif</p>
				<p>Instagram @if(!empty($snap->user->instagram))<a href="http://instagram.com/{{$snap->user->instagram}}" target="_blank">{{'@'.$snap->user->instagram}}</a>@else{{'-'}}@endif</p>
			</div>
		</div>

		<div id="commentsSection" class="comments-section">
			@include(
				'includes.comments', 
				array(
					'comments'=>$snap->comments, 
					'target_type'=>'StreetSnap', 
					'target_id'=>$snap->id
				)
			)
		</div>

		<div id="bannerSection" class="banner-section">
			<a href="{{action('InspirerRegisterController@showRegister')}}"><img src="http://cdn.camscon.kr/front-assets/single-banners/inspirer-register-banner-1.png" /></a>
		</div>
	</div>
</div><!--/.single-container-->
@stop

@section('footer_scripts')
<script type="text/javascript" src="{{asset('packages/jquery-ui-custom/jquery-ui-core-widget.1.11.2.min.js')}}"></script>
<!-- <script type="text/javascript" src="{{asset('packages/jquery-autoresize/jquery.autoresize.js')}}"></script> -->
<script type="text/javascript" src="{{asset('packages/handlebars/handlebars-v2.0.0.js')}}"></script>
<!-- <link rel="stylesheet" type="text/css" href="{{asset('packages/jquery-autoresize/autoresize.css')}}" /> -->
<script type="text/javascript">
var SingleView={
	snap:{
		id:"{{$snap->id}}"
	},
	login_status:@if(Auth::check()){{'true'}}@else{{'false'}}@endif,
	token:"{{csrf_token()}}",
	pins:@if($snap->pins->count()){{$snap->pins->toJson()}}@else{{'[]'}}@endif,
	scale:1,
	objects:{
		targetImg:null,
		pinContainer:null,
		pinList:null
	},
	init:function() {
		this.objects.targetImg=$('#snapPrimary').find('img');
		this.objects.pinContainer=$('#snapPrimary').find('.pin-container');
		this.objects.pinList=$('#pinList');

		this.render();

		this.objects.pinList.empty();
		var plen=this.pins.length;
		for(var i=0;i<plen;i++) {
			this.objects.pinList.append( this.createListItem(this.pins[i], i+1) );
		}

		this.objects.pinList.on('mouseover', 'li', {pinContainer:this.objects.pinContainer}, function(e) {
			var pin_id=$(this).attr('data-pin-no');
			e.data.pinContainer.find('a[data-id="'+pin_id+'"]').addClass('highlight');
		});

		this.objects.pinList.on('mouseout', 'li', {pinContainer:this.objects.pinContainer}, function(e) {
			var pin_id=$(this).attr('data-pin-no');
			e.data.pinContainer.find('a[data-id="'+pin_id+'"]').removeClass('highlight');
		});

		this.objects.pinContainer.on('mouseover', 'a', {pinList:this.objects.pinList}, function(e) {
			var pin_id=$(this).attr('data-id');
			e.data.pinList.find('li[data-pin-no="'+pin_id+'"]').addClass('highlight');
		});

		this.objects.pinContainer.on('mouseout', 'a', {pinList:this.objects.pinList}, function(e) {
			var pin_id=$(this).attr('data-id');
			e.data.pinList.find('li[data-pin-no="'+pin_id+'"]').removeClass('highlight');
		});

		//Track pin link clicks with Google Analytics
		this.objects.pinContainer.on('click', 'a', null, function() {
			ga('send', 'event', 'Pin', 'click', 'snap-pin');
		});

		this.objects.pinList.on('click', 'a', null, function() {
			ga('send', 'event', 'Pin', 'click', 'snap-pin-link');
		});

		//Register callback action to LoginModal if user is not logged in
		if(this.login_status===false) {
			//
		}
	}/*init()*/,
	render:function() {
		var maxWidth=$('#photoCol').width();
		var imgWidth=parseInt(this.objects.targetImg.attr('width'),10);
		if(imgWidth>maxWidth) {
			this.scale=maxWidth/imgWidth;
		} else {
			this.scale=1;
		}
		
		this.renderPins();
	},
	renderPins:function() {
		this.objects.pinContainer.empty();
		var plen=this.pins.length;
		for(var i=0;i<plen;i++) {
			this.objects.pinContainer.append( this.createPin(this.pins[i], i+1) );
		}
	}/*renderPins()*/,
	createPin:function(pin,number) {
		var newPin=$('<a href="" data-id="" class="pin"></a>').text(number);
		if(pin.links.length>0) {
			newPin.attr('href', pin.links[0].url);
			newPin.attr('target', '_blank');
		}
		newPin.attr('data-id', pin.id);
		newPin.css({
			top:parseFloat(pin.top)*this.scale,
			left:parseFloat(pin.left)*this.scale
		});
		return newPin;
	}/*createPin()*/,
	createListItem:function(pin,number) {
		var newItem=$('<li data-pin-no=""><span class="pin-numbering"></span><div class="data-wrapper"><div class="meta"><strong class="item-name"></strong> by <span class="vendor"></span></div><ul class="links"></div></ul></li>');
		newItem.attr('data-pin-no', pin.id);
		newItem.find('span.pin-numbering').text(number);
		newItem.find('strong.item-name').text(pin.item_category.name);
		newItem.find('span.vendor').text(pin.brand.name);

		var linkList=newItem.find('ul.links');
		var linkLen=pin.links.length;
		for(var k=0;k<linkLen;k++) {
			var newLink=$('<li class="pin-link"><span class="link-name"></span> <a href="" target="_blank"></a></li>');
			newLink.find('span.link-name').text(pin.links[k].title);
			newLink.find('a').attr('href', pin.links[k].url);
			newLink.find('a').text(pin.links[k].url);

			newLink.appendTo(linkList);
		}

		return newItem;
	}/*createListItem()*/
};//SingleView{}

var LikeButtons={
	login_status:@if(Auth::check()){{'true'}}@else{{'false'}}@endif,
	endpoints:{
		get_current_user_likes:"{{action('LikeController@getCurrentUserSnapLikes')}}"
	},
	token:"{{csrf_token()}}",
	init:function() {
		//Bind event handlers to like buttons
		$(document).on('click', '.like-btn', {likeAction:this.like.bind(this)}, function(e) {
			e.data.likeAction($(this));
		});

		//Bind callback to LoginModal{}
		if(this.login_status===false) {
			LoginModal.bindCallback(this.loginCallback.bind(this));
		}
	},
	like:function(btn) {
		btn.prop('disabled', true);
		var module=this;

		var data={
			_token:"{{csrf_token()}}",
			target_type:btn.attr('data-type'),
			target_id:btn.attr('data-id')
		}

		$.post("{{action('LikeController@procLike')}}", data, function(response) {
			//console.log(response);
			if(response.proc=='liked') {
				btn.addClass('liked');
				ga('send', 'event', 'Like', 'click', 'snap-like-single');
			} else if(response.proc=='canceled') {
				btn.removeClass('liked');
			}

			if('total' in response) {
				var totalLikesDisplay=module.findTotalLikesDisplay(response.target_type, response.target_id);
				totalLikesDisplay.text(response.total);
			}
		}, 'json').fail(function(response) {
			//console.log(response.status);
			if(response.status==401) {
				if(typeof LoginModal === 'object') {
					LoginModal.launch();
				}
			}
		}).always(function() {
			btn.prop('disabled', false);
		});
	},
	findTotalLikesDisplay:function(targetType, targetId) {
		return $('.total-likes[data-target-type="'+targetType+'"][data-target-id="'+targetId+'"]');
	},
	loginCallback:function() {
		var data={
			_token:this.token,
			targets:[]
		};

		$('button.like-btn').each(function() {
			data.targets.push($(this).attr('data-id'));
		});

		var updateLikes=this.updateLikes.bind(this);
		$.post(this.endpoints.get_current_user_likes, data, function(response) {
			//Validate response
			if(typeof(response)==='object' && 'type' in response && 'msg' in response && 'data' in response) {
				if(response.type==='success') {
					updateLikes(response.data);
				} else {
					console.error(response.msg);
				}
			} else {
				console.error('Invalid response');
			}
		}, 'json');
	},
	updateLikes:function(data) {
		if(data.constructor===Array) {
			var btns=$('button.like-btn');
			var dLen=data.length;
			for(var i=0;i<dLen;i++) {
				btns.filter('[data-id="'+data[i]+'"]').addClass('liked');
			}
		}
	}
};//LikeButtons{}

$(document).ready(function() {
	SingleView.init();
	LikeButtons.init();
	CommentsModule.init($('#commentsSection'));

	//Track banner clicks with Google Analytics
	$('#singleBannerLink').on('click', function() {
		ga('send', 'event', 'Banner', 'click', 'single-view-banner');
	});

	//Track category navigation, breadcrumbs and prev/next usage
	var vpw=$.viewportW();
	if(vpw<768) {
		$('.site-logo').on('click', 'a', null, function() {
			ga('send', 'event', 'LogoClick', 'click', 'logo-mobile');
		});
		$('.site-nav').on('click', 'a', null, function() {
			ga('send', 'event', 'SiteNav', 'click', 'site-nav-mobile');
		});
		$('.category-nav').on('click', 'a', null, function() {
			ga('send', 'event', 'CategoryNav', 'click', 'category-nav-mobile');
		});
		$('.breadcrumbs').on('click', 'a', null, function() {
			ga('send', 'event', 'Breadcrumbs', 'click', 'breadcrumbs-mobile');
		});
		$('.content-nav').on('click', 'a', null, function() {
			ga('send', 'event', 'ContentNav', 'click', 'content-nav-mobile');
		});
	} else {
		$('.site-logo').on('click', 'a', null, function() {
			ga('send', 'event', 'LogoClick', 'click', 'logo-desktop');
		});
		$('.site-nav').on('click', 'a', null, function() {
			ga('send', 'event', 'SiteNav', 'click', 'site-nav-desktop');
		});
		$('.category-nav').on('click', 'a', null, function() {
			ga('send', 'event', 'CategoryNav', 'click', 'category-nav-desktop');
		});
		$('.breadcrumbs').on('click', 'a', null, function() {
			ga('send', 'event', 'Breadcrumbs', 'click', 'breadcrumbs-desktop');
		});
		$('.content-nav').on('click', 'a', null, function() {
			ga('send', 'event', 'ContentNav', 'click', 'content-nav-desktop');
		});
	}
});

$(window).resize(function() {
	SingleView.render();
});
</script>
@stop