<?php

class StyleIconController extends BaseController {

	public function getListAll($slug='all', $ordering='new') {
		if(Route::current()->uri()=='/') {
			$ordering='hot';
		}

		//ViewData::add('fbLoginURL', action('AdminController@loginWithFB'));
		if(Request::ajax()) {
			//
		} else {
			return View::make('front.styleicon.list', ViewData::get());
		}
	}//getListHot()

	public function getListCampus($slug=null, $ordering='new') {
		return View::make('front.styleicon.list');
	}//getListCampus()

	public function getListStreet($slug=null, $ordering='new') {
		return View::make('front.styleicon.list');
	}//getListStreet()

	public function getListBrand($slug=null, $ordering='new') {
		return View::make('front.styleicon.list');
	}//getListCampus()

	public function getSingle($slug=null, $id=null) {
		//
	}//getSingle()

	public function showEditor($id=null) {
		if($id) {
			$icon=StyleIcon::find($id);
		} else {
			$icon=new StyleIcon;
			$icon->status='draft';
			$icon->save();
		}

		if($icon) {
			ViewData::add('icon', $icon);
			return View::make('front.styleicon.editor', ViewData::get());
		} else {
			return Redirect::to('error/not-found');
		}

		
	}//showEditor()

	public function uploadPrimary() {
		$response=new stdClass;
		$response->type=null;
		$response->data=null;

		//Check StyleIcon existence and ownership
		$input=Input::only('id');

		$icon=StyleIcon::with('user', 'primary')->find(intval($input['id']));
		if($icon) {
			//if(intval($icon->user->id)===intval(Auth::user()->id)) {
			if(true) {
				if(Input::hasFile('image')) {
					$img=new IconPrimaryImage;
					try {
						$img->setUploadedFile(Input::file('image'));
						$img->style_icon_id=0;
						$img->restrictWidth(670);

						if($img->save()) {
							//Remove old image
							/*
							if($icon->primary) {
								$icon->primary()->delete();
							}
							$img->icon()->save($icon);
							*/

							$response->type='success';
							$response->data=new \stdClass();
							$response->data->id=$img->id;
							$response->data->url=$img->url;
							$response->data->width=$img->width;
							$response->data->height=$img->height;

						} else {
							throw new Exception("Failed to save file.", 1000);
							
						}
					} catch(Exception $e) {
						$response->type='error';
						$response->data='file_proc';
					}
				} else {
					$response->type='error';
					$response->data='no_file';
				}
			} else {
				$response->type='error';
				$response->data='not_owner';
			}
		} else {
			$response->type='error';
			$response->data='not_found';
		}

		return Response::json($response);
	}//uploadPrimary()

	public function uploadAttachment() {
		//
	}//uploadAttachment()

	public function saveStyleIcon() {
		//
	}//saveStyleIcon()

}
