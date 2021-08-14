<?php
error_reporting(E_ERROR | E_PARSE);
// connect class
class ac_POP3SpamMail{
    private $template_path;
    private $serv;
    private $port;
    private $login;
    private $passw;
    
    public $mailList = array(); 
    public $topicList = array();

    function __construct() {
        $this->template_path = __DIR__;
    }
    
    public function set_connection($settings){
        $this->serv = $settings[2];
        $this->port = $settings[3];
        $this->login = $settings[0];
        $this->passw = $settings[1];
    }
    
    public function set_mailfilter($param) {
        $this->mailList = $param;
    }
    
    public function set_titlefilter($param) {
        $this->topicList = $param;
    }
    
    public function connect(){ 
        $mbox = imap_open("{".$this->serv.":".$this->port."/imap/ssl/novalidate-cert}INBOX", $this->login, $this->passw) or die('Cannot connect: '.exit());       
		$this->addlog('Logged in: '.$this->login);
        $this->check_post_imap($mbox, $this->login);
    }
    
    private function check_post_imap($mbox, $login){
        // search in emailbox
		$this->addlog('== search in emailbox: '.$this->login);
        $old_msg_numbers = imap_search($mbox, "");
        $lists = imap_list($mbox, "{".$this->serv."}", "*");
        $MC = imap_check($mbox);
        // Fetch an overview for all messages in INBOX
        $result = imap_fetch_overview($mbox,"1:{$MC->Nmsgs}",0);
        $result = array_reverse($result);
        
        // get filters array
        $emails = $this->mailList;
        $titles = $this->topicList;
        
		$counter = 0;
        foreach ($result as $overview) {
            // Topics
            if(count($titles) != 0){
				if($counter == 0){
					$this->addlog('== topic filter: '.count($titles));
				}
                foreach ($titles as $title) {
                    $subject = $this->imap_utf8_fix($overview->subject);
                    if (strpos($subject, $title) !== FALSE) {
                        $this->remove_post_imap($mbox, $overview->msgno);
                        $this->addlog('== Remove message topic: '.$subject);
                    }
                }      
            }
            // Email
            if(count($emails) != 0){
				if($counter == 0){
					$this->addlog('== email filter: '.count($emails));
				}
                foreach ($emails as $email) {
                    if (strpos($overview->from, $email) !== FALSE) {
                        $this->remove_post_imap($mbox, $overview->msgno);
                        $this->addlog('== Remove message email: '.$overview->from.' filter: '.$email);
                    }
                    if (strpos($overview->message_id, $email) !== FALSE) {
                        $this->remove_post_imap($mbox, $overview->msgno);
                        $this->addlog('== Remove message id: '.$overview->message_id.' filter: '.$email);
                    }
                }
            }
			$counter++;
        }
		$this->addlog('== all emails checked: '.$counter);
        imap_close($mbox); 
		$this->addlog('== close emailbox: '.$this->login);		
    }
    
    // decode subject utf
    private function imap_utf8_fix($string) {
        return iconv_mime_decode($string,0,"UTF-8");
    } 
    
    // remove message from mailbox
    private function remove_post_imap($mbox, $id){
        $check = imap_mailboxmsginfo($mbox);
        //echo "Messages before delete: " . $check->Nmsgs . "<br />\n";
        imap_delete($mbox, $id);
        $check = imap_mailboxmsginfo($mbox);
        //echo "Messages after  delete: " . $check->Nmsgs . "<br />\n";
        imap_expunge($mbox);
        $check = imap_mailboxmsginfo($mbox);
        //echo "Messages after expunge: " . $check->Nmsgs . "<br />\n";
        //@imap_close($mbox);
    }
    
    private function addlog($msg) {
        if(function_exists('ac_make_log_file')) {
            ac_make_log_file($msg);
			$this->echoMessage($msg);
        }else{
			$this->echoMessage($msg);
		}
    }
    public function echoMessage($msg){
		echo '<p>'.$msg.'</p>';
	}
	
	public function loadSmapMailList($file){
		$fileStr = file_get_contents($file, true);
		return explode(',', $fileStr);
	}
} 

// filter list
$titles = array();

$emails = array();

// array emails
$mailArray = array(
	array('Login', 'Password', 'Server', 'Port'), //email settings
);

// run main script
if(count($mailArray) != 0){
    $mail = new ac_POP3SpamMail; 
	//encode filter file
	$emails = $mail->loadSmapMailList('mail.txt'); // load file 
    $mail->set_mailfilter($emails);
    $mail->set_titlefilter($titles);

    foreach ($mailArray as $email){
        $mail->set_connection($email);
        $mail->connect();
    }
}
