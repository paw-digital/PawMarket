<?php
namespace Paw\Controller;

use Paw\AbstractController;

class ProfileController extends AbstractController
{
	public function show_profile()
	{
		$user = FALSE;
		if(isset($_GET['id']))
			$user = $this->getDb()->market_account_by_id($_GET['id']);
		
        $this->addHeader();
        $this->addTemplate('partials/profile.phtml', [
            "USER" =>  $user
        ]);
        $this->addFooter();
        return $this->getResponse();
	}
	public function edit_profile_form($err = false)
	{
		$this->generateSessionToken();
		
		$user = FALSE;
		if(isset($_GET['id']))
			$user = $this->getDb()->market_account_by_id($_GET['id']);
		
        $this->addHeader();
        $this->addTemplate('partials/profile_edit.phtml', [
            "err" =>  isset($err) ? $err : false,
            "USER" =>  $user
        ]);
        $this->addFooter();
        return $this->getResponse();
	}
    public function edit_profile_submit()
    {
		$form_data = $this->check_profile_form();
		
		$user = FALSE;
		if(isset($_GET['id']))
			$user = $this->getDb()->market_account_by_id($_GET['id']);
		
		// No form errors? Update listing..
		if($user && !$form_data['err'] && $form_data['user_id'] == $this->user->id)
		{
			$this->getDb()->market_account_update($form_data['user_id'], $form_data['email'], $form_data['telegram'], $form_data['discord']);
			header('Location: /profile?id='.$user->id);
			die();
		}

        return $this->edit_profile_form($form_data['err']);
    }
	private function check_profile_form()
	{
		$err = FALSE;
		
		if ($_POST['_token'] !== $_SESSION['_token']) {
			$err = "Token mismatch.";
		}
		if(!$this->user) {
			$err = "You are not logged in.";
		}
		if(!isset($_GET['id']) || !isset($_POST['email']) || !isset($_POST['telegram']) || !isset($_POST['discord'])) {
			$err = 'Not all fields submitted..';
		}
		if(empty($_POST['email']) && empty($_POST['discord']) && empty($_POST['telegram'])) {
			$err = "Fill out at least one of the contact fields you can be reached at";
		}
		if(empty($_GET['id'])) {
			$err = "No user id specified.";
		}
		if($err)
			return array('err' => $err);
		
		$form_data = FALSE;
		$form_data['err'] = $err;
		$form_data['email'] = $_POST['email'];
		$form_data['telegram'] = $_POST['telegram'];
		$form_data['discord'] = $_POST['discord'];
		$form_data['user_id'] = $_GET['id'];
		
		return $form_data;
	}
	private function generateSessionToken()
	{
		$token = md5(uniqid(rand(), true));
		$_SESSION['_token'] = $token;
	}
}