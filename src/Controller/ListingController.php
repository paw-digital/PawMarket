<?php
namespace Paw\Controller;

use Paw\AbstractController;

class ListingController extends AbstractController
{
    public function get($err = false)
    {
		return $this->add_listing_form();
    }
	public function show_listing()
	{
		$listing = FALSE;
		$user = FALSE;
		if(isset($_GET['id']))
		{
			$listing = $this->getDb()->market_listing_by_id($_GET['id']);
			if($listing)
				$user = $this->getDb()->market_account_by_id($listing->account_id);
		}
		
        $this->addHeader();
        $this->addTemplate('partials/listing.phtml', [
            "LISTING" =>  $listing,
			"LISTING_USER" => $user
        ]);
        $this->addFooter();
        return $this->getResponse();
	}
    public function add_listing_form($err = false)
    {
		$this->generateSessionToken();
		
        $this->addHeader();
        $this->addTemplate('partials/listing_add.phtml', [
            "err" =>  isset($err) ? $err : false
        ]);
        $this->addFooter();
        return $this->getResponse();
	}
	public function edit_listing_form($err = false)
	{
		$this->generateSessionToken();
		
		$listing = FALSE;
		if(isset($_GET['id']))
			$listing = $this->getDb()->market_listing_by_id($_GET['id']);
		
		if(isset($_GET['remove_image']) && $listing && $this->user->id == $listing->account_id)
		{
			$this->getDb()->market_listing_remove_image($listing->id);
			unlink('uploads/'.$listing->image_path);
			$listing->image_path = '';
		}
		
        $this->addHeader();
        $this->addTemplate('partials/listing_edit.phtml', [
            "err" =>  isset($err) ? $err : false,
            "LISTING" =>  $listing
        ]);
        $this->addFooter();
        return $this->getResponse();
	}
    public function add_listing_submit()
    {
		$form_data = $this->check_listing_form();
		
		// No form errors? Add listing..
		if(!$form_data['err'])
		{
			$listing_id = $this->getDb()->market_add_listing($form_data['user_id'], $form_data['type'], $form_data['title'], $form_data['body'], $form_data['price_usd'], $form_data['price_paw'], $form_data['image_path']);
			header('Location: /listing?id='.$listing_id);
			die();
		}

        return $this->add_listing_form($form_data['err']);
    }
    public function edit_listing_submit()
    {
		$form_data = $this->check_listing_form();
		
		$listing = FALSE;
		if(isset($_GET['id']))
			$listing = $this->getDb()->market_listing_by_id($_GET['id']);
		
		// No form errors? Update listing..
		if($listing && $listing->account_id == $this->user->id && !$form_data['err'])
		{
			$image_path = $form_data['image_path'];
			if(empty($image_path))
				$image_path = $listing->image_path;
			else if(!empty($listing->image_path)) // had image before? delete it.
				unlink('uploads/'.$listing->image_path);
			
			$this->getDb()->market_update_listing($listing->id, $listing->account_id, $form_data['type'], $form_data['title'], $form_data['body'], $form_data['price_usd'], $form_data['price_paw'], $image_path);
			header('Location: /listing?id='.$listing->id);
			die();
		}

        return $this->edit_listing_form($form_data['err']);
    }
    public function delete_listing()
    {
		$listing = FALSE;
		if(isset($_GET['id']))
			$listing = $this->getDb()->market_listing_by_id($_GET['id']);
		
		if($listing && $listing->account_id == $this->user->id)
		{
			if(!empty($listing->image_path))
				unlink('uploads/'.$listing->image_path);
			$this->getDb()->market_delete_listing($listing->id);
			header('Location: /?msg=ldeleted');
			die();
		}

        header('Location: /listing?id='.$listing->id);
		die();
    }
	private function check_listing_form()
	{
		$err = FALSE;
		
		// Check Anti-CSRF token 
		if ($_POST['_token'] !== $_SESSION['_token'])
		{
			$err = "Token mismatch.";
		}
		/*
		if(!isset($_POST['terms'])) {
			$err = 'Terms must be accepted';
		}*/
		if(!$this->user) {
			$err = "You are not logged in.";
		}
		if(!\Paw\Helper::hasContactDetails($this->user)) {
			$err = "No contact details specified in your profile!";
		}
		if((!isset($_POST['title']) || empty($_POST['title'])) || (!isset($_POST['body']) || empty($_POST['body']))) {
			$err = "Title and description of listing must be specified.";
		}
		if(!isset($_POST['type']) || $_POST['type'] > 1 || $_POST['type'] < 0) {
			$err = "Specify listing type.";
		}
		$price_paw = 0;
		$price_usd = 0;
		if(isset($_POST['price_usd']) && !empty($_POST['price_usd']))
		{
			$price_usd = trim(str_replace(',','',$_POST['price_usd']));
			if($price_usd > 10000 || $price_usd < 0.10)
			{
				$err = "Specify a price between $0.10 and $10,000.";
			}
		}
		else if(isset($_POST['price_paw']) && !empty($_POST['price_paw']))
		{
			$price_paw = trim(str_replace(',','',$_POST['price_paw']));
			if($price_paw > 1000000000 || $price_paw < 1000)
			{
				$err = "Specify a price between 1000 and 1,000,000,000.";
			}
		}
		if(!$price_paw && !$price_usd) {
			$err = "Specify the price.";
		}
		
		$type = isset($_POST['type']) && $_POST['type'] === '1' ? 1 : 0; // 0 = sell, 1 = buy
		$title = $_POST['title'];
		$body = $_POST['body'];
		$image_path = '';
			
        try {
			// Handle image file upload
			if(isset($_FILES[ 'uploaded' ]) && !empty($_FILES['uploaded']['tmp_name'])) {
				// File information 
				$uploaded_name = $_FILES[ 'uploaded' ][ 'name' ]; 
				$uploaded_ext  = substr( $uploaded_name, strrpos( $uploaded_name, '.' ) + 1); 
				$uploaded_size = $_FILES[ 'uploaded' ][ 'size' ]; 
				$uploaded_type = $_FILES[ 'uploaded' ][ 'type' ]; 
				$uploaded_tmp  = $_FILES[ 'uploaded' ][ 'tmp_name' ]; 
				$detectedType = exif_imagetype($_FILES['uploaded']['tmp_name']);
				if($detectedType == IMAGETYPE_PNG)
				{
					$uploaded_type = 'image/png';
					$target_ext = 'png';
				}
				else if($detectedType == IMAGETYPE_JPEG)
				{
					$uploaded_type = 'image/jpeg';
					$target_ext = 'jpg';
				}

				// Where are we going to be writing to? 
				$target_path   = 'uploads/'; 
				$target_file   =  md5( uniqid() . $uploaded_name ) . '.' . $target_ext; 
				$temp_file     = ( ( ini_get( 'upload_tmp_dir' ) == '' ) ? ( sys_get_temp_dir() ) : ( ini_get( 'upload_tmp_dir' ) ) ); 
				$temp_file    .= DIRECTORY_SEPARATOR . md5( uniqid() . $uploaded_name ) . '.' . $uploaded_ext; 

				
				// Is it an image? 
				if( ( strtolower( $uploaded_ext ) == 'jpg' || strtolower( $uploaded_ext ) == 'jpeg' || strtolower( $uploaded_ext ) == 'png' ) && 
					( $uploaded_size < 1000000 ) && 
					( $uploaded_type == 'image/jpeg' || $uploaded_type == 'image/png' ) && 
					getimagesize( $uploaded_tmp ) ) { 
					
					// Strip any metadata, by re-encoding image (Note, using php-Imagick is recommended over php-GD) 
					if( $uploaded_type == 'image/jpeg' ) { 
									
						$img = imagecreatefromjpeg( $uploaded_tmp ); 
						imagejpeg( $img, $temp_file, 100); 
									} 
					else { 
						$img = imagecreatefrompng( $uploaded_tmp ); 
						// removing the black from the placeholder
						imagecolortransparent($img, $background);
						// turning off alpha blending (to ensure alpha channel information
						// is preserved, rather than removed (blending with the rest of the
						// image in the form of black))
						imagealphablending($img, false);
						// turning on alpha channel information saving (to ensure the full range
						// of transparency is preserved)
						imagesavealpha($img, true);
						
						imagepng( $img, $temp_file, 9); 
					} 
					imagedestroy( $img ); 

					// Can we move the file to the web root from the temp folder? 
					if( rename( $temp_file, ( getcwd() . DIRECTORY_SEPARATOR . $target_path . $target_file ) ) ) { 
						// Yes! 
						$target = getcwd() . DIRECTORY_SEPARATOR . $target_path . $target_file;
						$image_path = $target_file;
					} 
					else { 
						// No 
						$err = '<pre>Your image was not uploaded.</pre>'; 
					} 

					// Delete any temp files 
					if( file_exists( $temp_file ) ) 
						unlink( $temp_file ); 
				} 
				else { 
					// Invalid file 
					$err = '<pre>Your image was not uploaded. We can only accept JPEG or PNG images.</pre>'; 
				}
			}
			
        } catch (\Exception $e) {
            $err =  "Error: " . $e->getMessage();
        }
		
		
		
		$form_data = FALSE;
		$form_data['err'] = $err;
		$form_data['user_id'] = $this->user->id;
		$form_data['type'] = $type;
		$form_data['title'] = $title;
		$form_data['body'] = $body;
		$form_data['price_usd'] = $price_usd;
		$form_data['price_paw'] = $price_paw;
		$form_data['image_path'] = $image_path;
		
		return $form_data;
	}
	private function generateSessionToken()
	{
		$token = md5(uniqid(rand(), true));
		$_SESSION['_token'] = $token;
	}
}