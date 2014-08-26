<?php

class ImageAttachment extends Eloquent {

	protected $appends = array('uploaded_file', 'remote_file', 'source_type', 'relative_path', 'url');


	/*-------------------------------------------------*/
	/* Protected properties
	/*-------------------------------------------------*/

	protected $uploaded_file=null;
	protected $remote_file=null;
	protected $source_type=null;
	protected $relative_path=null;


	/*-------------------------------------------------*/
	/* Accessors
	/*-------------------------------------------------*/

	public function getUploadedFileAttribute() {
		return $this->uploaded_file;
	}//getUploadedFileAttribute()

	public function getRemoteFileAttribute() {
		return $this->remote_file;
	}//getRemoteFileAttribute()

	public function getSourceTypeAttribute() {
		return $this->source_type;
	}//getSourceTypeAttribute()

	public function getRelativePathAttribute() {
		return $this->relative_path;
	}//getRelativePathAttribute()

	public function getUrlAttribute() {
		if(isset($this->id,$this->original_extension,$this->dir_path,$this->filename)) {
			return url( sprintf('%s/%s.%s', $this->dir_path, $this->filename, $this->original_extension) );
		} else {
			return false;
		}
	}//getUrlAttribute()


	/*-------------------------------------------------*/
	/* File setters
	/*-------------------------------------------------*/

	public function setUploadedFile(Symfony\Component\HttpFoundation\File\UploadedFile $file) {

		/*-------------------------------------------------*/
		/* UploadedFile extends SplFileInfo
		/*-------------------------------------------------*/

		if($file->isValid()) {
			//Set basic file information
			$this->original_name=$file->getClientOriginalName();
			$this->original_extension=$file->getClientOriginalExtension();
			$this->mime_type=$file->getMimeType();
			$this->size=$file->getSize();

			$uploadedPath=$file->getRealPath();
			$imageSize=getimagesize($uploadedPath);
			if($imageSize===false) {
				throw new Exception('Uploaded file is not an acceptable image.',102);
			} else {
				//Set image size
				$this->width=$imageSize[0];
				$this->height=$imageSize[1];

				//Store file in uploaded_file
				$this->uploaded_file=$file;
				$this->source_type='uploaded';
			}

			return true;
		} else {
			throw new Exception('Uploaded file is not valid.',101);

			return false;
		}
	}//setUploadedFile()

	public function setRemoteFile($url) {
		$imgContent=file_get_contents($url);
		if($imgContent===false) {
			throw new Exception('Remote resource is not available.',201);
		} else {
			//Parse response header
			$filesize=null;$mimetype=null;

			//Content-Length
			$contentLengthPattern='/^content-length/';
			foreach($http_response_header as $header) {
				if(preg_match($contentLengthPattern, strtolower($header))===1) {
					$headerArray=explode(':', $header);
					$filesize=intval(trim(end($headerArray)));
					if($filesize==0) {
						$filesize=null;
					}
					break;
				}
			}

			//Content-Type
			$contentTypePattern='/^content-type/';
			foreach($http_response_header as $header) {
				if(preg_match($contentTypePattern, strtolower($header))===1) {
					$headerArray=explode(':', $header);
					$mimetype=trim(strtolower(end($headerArray)));
					break;
				}
			}

			if(isset($filesize,$mimetype)) {
				$this->size=$filesize;
				$this->mime_type=$mimetype;
				//Get file extension from mime type
				$extension=$this->get_extension($mimetype);
				if(!$extension) {
					throw new Exception('Remote resource mime type is not supported.',203);
				} else {
					$this->original_extension=$extension;
				}

				//Create gd image resource
				$img=imagecreatefromstring($imgContent);
				if($img===false) {
					throw new Exception('Remote resource is not an image.',204);
				} else {
					//Get image size
					$width=imagesx($img);
					$height=imagesy($img);
					if(isset($width,$height)) {
						$this->width=$width;
						$this->height=$height;
						$this->remote_file=$imgContent;
						$this->source_type='remote';
					} else {
						throw new Exception('Failed to access image size.',205);
					}
				}
			} else {
				throw new Exception('Headers not available.',202);
			}

		}
	}//setRemoteFile()


	/*-------------------------------------------------*/
	/* Helpers
	/*-------------------------------------------------*/

	public function generate_path($relative_path) {
		/*-------------------------------------------------*/
		/* Generates storage path and returns absolute path
		/* Creates new directory structure if needed
		/* dir_path property of model is set (relative to public_path())
		/*-------------------------------------------------*/

		//Clean relative path
		$relative_path=str_replace('..', '', $relative_path);
		$relative_path=str_replace(' ', '', $relative_path);
		$relative_path=trim($relative_path, '/');

		$absolute_path=public_path().'/'.$relative_path;
		$sub_path=date('/Y/m', time() );
		$absolute_path.=$sub_path;

		//Create new directory structure if needed
		if(file_exists($absolute_path)) {
			if(!is_dir($absolute_path)) {
				//absolute_path exists as file
				return false;

			}
		} else {
			//Create directory (rwx-rwx-r--)
			if( mkdir($absolute_path, 0774, true)!==true ) {
				//failed to create directory
				return false;
			}
		}

		//Check dir writable
		if(!is_writable($absolute_path)) {
			//absolute_path is not writable
			return false;
		}

		//Set dir_path property in model
		$this->dir_path=$relative_path.$sub_path;

		//Return the absolute path
		return $absolute_path;
	}//generate_path()

	public function generate_filename() {
		//return pseudo random filename
		return str_random(32);
	}//generate_filename()

	private function get_extension($mime_type) {
		$mimeTypes=array(
			'image/jpeg'=>'jpeg',
			'image/pjpeg'=>'jpeg',
			'image/png'=>'png',
			'image/gif'=>'gif'
		);

		if(isset($mimeTypes[$mime_type])) {
			return $mimeTypes[$mime_type];
		} else {
			return null;
		}
	}//get_extension()


	/*-------------------------------------------------*/
	/* Bind model event handlers
	/*-------------------------------------------------*/

	public static function boot() {
		parent::boot();

		static::saving(function($attachment) {
			if($attachment->source_type=='uploaded') {
				if($attachment->uploaded_file instanceof Symfony\Component\HttpFoundation\File\UploadedFile) {
					$absolute_path=$attachment->generate_path($attachment->relative_path);
					if($absolute_path) {
						//Generate filename
						$filename=$attachment->generate_filename();
						while(file_exists($absolute_path.'/'.$filename.'.'.$attachment->original_extension)) {
							$filename=$attachment->generate_filename();
						}
						//Move file
						try {
							$attachment->uploaded_file->move($absolute_path, $filename.'.'.$attachment->original_extension);
						} catch(FileException $e) {
							//Failed to move file
							return false;
						}

						//File has been moved!!!
						$attachment->filename=$filename;
					} else {
						//Path generation failed
						return false;
					}
				} else {
					//uploaded_file is not an instance of UploadedFile
					return false;
				}
			} elseif($attachment->source_type=='remote') {
				if(!empty($attachment->remote_file)) {
					$absolute_path=$attachment->generate_path($attachment->relative_path);
					if($absolute_path) {
						//Generate filename
						$filename=$attachment->generate_filename();
						while(file_exists($absolute_path.'/'.$filename.'.'.$attachment->original_extension)) {
							$filename=$attachment->generate_filename();
						}
						//Store file
						if( file_put_contents($absolute_path.'/'.$filename.'.'.$attachment->original_extension, $attachment->remote_file)===false ) {
							//Failed to store file
							return false;
						} else {
							//File has been stored!!!
							$attachment->filename=$filename;
						}
					} else {
						//Path generation failed
						return false;
					}
				} else {
					//remote_file is not set
					return false;
				}
			} else {
				//source_type is not set
				return false;
			}
		});//static::saving()
	}//boot()

}