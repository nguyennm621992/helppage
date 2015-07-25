<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

define ("HMAILADMIN_PATH", "/home/HanbiroMailcore/bin/hmailadmin");

class Vpopmail 
{
	public function __construct()
	{

	}
	
	/*
	vadddomain: usage: vadddomain [options] virtual_domain [postmaster password]
	options: -v prints the version
         -q quota_in_bytes (sets the quota for postmaster account)
         -b (bounces all mail that doesn't match a user, default)
         -e email_address (forwards all non matching user to this address [*])
         -u user (sets the uid/gid based on a user in /etc/passwd)
         -d dir (sets the dir to use for this domain)
         -i uid (sets the uid to use for this domain)
         -g gid (sets the gid to use for this domain)
         -O optimize adding, for bulk adds set this for all
            except the last one
         -r[len] (generate a len (default 8) char random postmaster password)
	*/
	public function add_domain($domain, $password) 
	{
		return $this->vpopmail_readline(sprintf("A %s \'%s\'", $domain, $password));
	}

	/*
	vdeldomain: usage: [options] domain_name
	options: -v (print version number)
	options: -f (force delete of virtual domains)
	*/
	public function delete_domain($domain) 
	{
		return $this->vpopmail_readline(sprintf("B -f %s", $domain));
	}

	/*
	vadduser: usage: [options] email_address [passwd]
	options: -v (print the version)
         -q quota_in_bytes (sets the users quota, use NOQUOTA for unlimited)
         -c comment (sets the gecos comment field)
         -e standard_encrypted_password
         -n no_password
         -r[len] (generate a len (default 8) char random password)
	*/
	public function add_user($email, $name, $password, $quota='NOQUOTA') 
	{
		return $this->vpopmail_readline(sprintf("C -q \'%s\' -c \'%s\' %s \'%s\'", $quota, $name, $email, $password));
	}

	/*
	vdeluser: usage: [options] email_address
	options: -v (print version number)
	*/
	public function delete_user($email) 
	{
		return $this->vpopmail_readline(sprintf("D %s", $email));
	}

	/*
	vmoduser: usage: [options] email_addr or domain (for each user in domain)
	options: -v ( display the vpopmail version number )
         -n ( don't rebuild the vpasswd.cdb file )
         -q quota ( set quota )
         -c comment (set the comment/gecos field )
         -e encrypted_passwd (set the password field )
         -C clear_text_passwd (set the password field )
	the following options are bit flags in the gid int field
         -u ( set no dialup flag )
         -d ( set no password changing flag )
         -p ( set no pop access flag )
         -s ( set no smtp access flag )
         -w ( set no web mail access flag )
         -i ( set no imap access flag )
         -b ( set bounce mail flag )
         -o ( set override domain limits flag )
         -r ( set no external relay flag )
         -a ( grant qmailadmin administrator privileges)
         -0 ( set V_USER0 flag )
         -1 ( set V_USER1 flag )
         -2 ( set V_USER2 flag )
         -3 ( set V_USER3 flag )
         -x ( clear all flags )
	*/
	public function modify_user($email, $password='', $quota='') 
	{
		if (!empty($password)) $password = " -C \'" . $password . "\'";
		if (!empty($quota)) $quota = " -q \'" . $quota . "\'";
		return $this->vpopmail_readline(sprintf("Z %s %s %s", $password, $quota, $email));
	}

	/*
	vpasswd: usage: [options] email_address [password]
	options: -v (print version number)
         -r generate a random password
	*/
	public function change_password($email, $password) 
	{
		return $this->vpopmail_readline(sprintf("E %s '%s'", $email, $password));
	}

	/*
	vuserinfo: usage: [options] email_address
	options: -v (print version number)
         -a (display all fields, this is the default)
         -n (display name field)
         -p (display crypted password)
         -u (display uid field)
         -g (display gid field)
         -c (display comment field)
         -d (display directory)
         -q (display quota field)
         -Q (display quota usage)
         -C (display clear text password)
         -l (display last authentication time)
         -D domainname (show all users on this domain)
	*/
	public function get_user_passwd($email) 
	{
		return $this->vpopmail_readline(sprintf("G %s", $email));
	}

	public function get_user_name($email) 
	{
		return $this->vpopmail_readline(sprintf("H %s", $email));
	}

	public function get_user_directory($email) 
	{
		return $this->vpopmail_readline(sprintf("I %s", $email));
	}

	public function get_user_quota($email) 
	{
		return $this->vpopmail_readline(sprintf("J %s", $email));
	}

	public function get_user_quota_usage($email) 
	{
		return $this->vpopmail_readline(sprintf("K %s", $email));
	}

	/*
	usage: vpopbull [options] -f [email_file] [virtual_domain] [...]
       -v (print version number)
       -V (verbose)
       -f email_file (file with message contents)
       -e exclude_email_addr_file (list of addresses to exclude)
       -n (don't mail. Use with -V to list accounts)
       -c (default, copy file)
       -h (use hard links)
       -s (use symbolic links)
	*/
	public function get_email_addresses($domain) 
	{
		return $this->vpopmail_readline(sprintf("L %s", $domain));
	}

	/*
	vsetuserquota: [options] email_address|domain_name quota
	options:
	-v (print version number)
	*/
	public function set_user_quota($email, $quota) 
	{
		return $this->vpopmail_readline(sprintf("M %s %s", $email, $quota));
	}

	/*
	valias: usage: [options] email_address 
	options: -v ( display the vpopmail version number )
         -n ( show alias names, use just domain )
         -s ( show aliases, can use just domain )
         -d ( delete alias )
         -i alias_line (insert alias line)

	Example: valias -i fred@inter7.com bob@inter7.com
         (adds alias from bob@inter7.com to fred@inter7.com
	*/
	public function set_user_alias($real, $alias) 
	{
		return $this->vpopmail_readline(sprintf("N %s '%s'", $real, $alias));
	}

	public function del_user_alias($email) 
	{
		return $this->vpopmail_readline(sprintf("O %s", $email));
	}

	public function get_domain_aliases($domain) 
	{
		return $this->vpopmail_readline(sprintf("W %s", $domain));
	}

	/*
	vaddaliasdomain: usage: [options] real_domain alias_domain
	options: -v (print version number)
	*/
	public function set_domain_alias($alias, $real) 
	{
		return $this->vpopmail_readline(sprintf("P %s %s", $alias, $real));
	}

	/*
	vdominfo: usage: [options] [domain]
	options: -v (print version number)
			 -a (display all fields, this is the default)
			 -n (display domain name)
			 -u (display uid field)
			 -g (display gid field)
			 -d (display domain directory)
			 -t (display total users)
			 -r (display real domain)
	*/
	public function domain_exists_check($domain) 
	{
		return $this->vpopmail_readline(sprintf("Y %s", $domain));
	}

	public function get_domain_list() 
	{
		return $this->vpopmail_readline("Q");
	}
	
	public function get_domain_user_total($domain) 
	{
        return $this->vpopmail_readline(sprintf("R %s", $domain));
    }

    public function get_domain_directory($domain) 
    {
        return $this->vpopmail_readline(sprintf("S %s", $domain));
    }

	/*
	도메인의 게정 수제한은 도메인 아래의 .qmailadmin-limits 안에 
	maxpopaccounts:100
	maxaliases: 100
	maxforwards: -1
	maxautoresponders: -1
	maxmailinglists: -1
	*/
    public function get_domain_limits($domain) 
    {
        return $this->vpopmail_readline(sprintf("T %s", $domain));
    }

	public function set_domain_limits($domain, $num) 
	{
        return $this->vpopmail_readline(sprintf("U %s %s", $domain, $num));
    }

	/*
	vmoduser: usage: [options] email_addr or domain (for each user in domain)
	options: -v ( display the vpopmail version number )
			 -n ( don't rebuild the vpasswd.cdb file )
			 -q quota ( set quota )
			 -c comment (set the comment/gecos field )
			 -e encrypted_passwd (set the password field )
			 -C clear_text_passwd (set the password field )
	the following options are bit flags in the gid int field
			 -x ( clear all flags )
			 -d ( don't allow user to change password )
			 -p ( disable POP access )
			 -s ( disable SMTP AUTH access )
			 -w ( disable webmail [IMAP from localhost*] access )
				( * full list of webmail server IPs in vchkpw.c )
			 -i ( disable non-webmail IMAP access )
			 -b ( bounce all mail )
			 -o ( user is not subject to domain limits )
			 -r ( disable roaming user/pop-before-smtp )
			 -a ( grant qmailadmin administrator privileges )
			 -S ( grant system administrator privileges - access all domains )
			 -E ( grant expert privileges - edit .qmail files )
			 -f ( disable spamassassin)
			 -F ( delete spam)
	  [The following flags aren't used directly by vpopmail but are]
	  [included for other programs that share the user database.]
			 -u ( set no dialup flag )
			 -0 ( set V_USER0 flag )
			 -1 ( set V_USER1 flag )
			 -2 ( set V_USER2 flag )
			 -3 ( set V_USER3 flag )

	*/
	public function modify_user_name($name, $email) 
	{
		return $this->vpopmail_readline(sprintf("V \'%s\' %s", $name, $email));
	}

	public function modify_user_alias($real, $alias) 
	{
		return $this->vpopmail_readline(sprintf("X %s '%s'", $real, $alias));
	}

	public function get_user_whitelist($email) 
	{
		return $this->vpopmail_readline(sprintf("AA %s", $email));
	}

	public function user_whitelist_create($email, $email_list) 
	{
		return $this->vpopmail_readline(sprintf("BB %s %s", $email, $email_list));
	}

	public function get_user_whitelist_path($email) 
	{
		return $this->vpopmail_readline(sprintf("CC %s", $email));
	}

	public function user_whitelist_add($email, $email_list) 
	{
		return $this->vpopmail_readline(sprintf("DD %s %s", $email, $email_list));
	}

	public function get_user_config($email) 
	{
		return $this->vpopmail_readline(sprintf("EE %s", $email));
	}

	public function get_domain_config($domain) 
	{
		return $this->vpopmail_readline(sprintf("FF %s", $domain));
	}

	public function get_server_config() 
	{
		return $this->vpopmail_readline("GG");
	}

	public function set_user_config($email, $data) 
	{
		return $this->vpopmail_readline(sprintf("HH %s %s", $email, $data));
	}

	public function set_domain_config($domain, $data) 
	{
		return $this->vpopmail_readline(sprintf("II %s %s", $domain, $data));
	}

	public function set_server_config($data) 
	{
		return $this->vpopmail_readline(sprintf("JJ %s", $data));
	}

	public function set_user_alias_config($alias_email, $local_email) 
	{
		return $this->vpopmail_readline(sprintf("KK %s '%s'", $alias_email, $local_email));
	}

	public function get_user_alias_config_null($email) 
	{
		return $this->vpopmail_readline(sprintf("LL %s", $email));
	}

	public function get_user_alias_config($email) 
	{
		return $this->vpopmail_readline(sprintf("MM %s", $email));
	}

	public function get_vpopmail_db_info() 
	{
		return $this->vpopmail_readline("NN");
	}

	public function set_user_config_create($email) 
	{
		return $this->vpopmail_readline(sprintf("OO %s", $email));
	}

	public function set_basic_baysian($email) 
	{
		return $this->vpopmail_readline(sprintf("PP %s", $email));
	}

	public function user_whitelist_delete($email) 
	{
		return $this->vpopmail_readline(sprintf("QQ %s", $email));
	}

	public function set_domain_quota($domain, $quota) 
	{
		return $this->vpopmail_readline(sprintf("RR %s %s", $domain, $quota));
	}

	public function get_domain_quota($domain) 
	{
		return $this->vpopmail_readline(sprintf("SS %s", $domain));
	}

	public function set_user_login_check($email, $data) 
	{
		$data = base64_encode($data);
		return $this->vpopmail_readline(sprintf("TT %s %s", $email, $data));
	}

	public function get_quota($email) 
	{
		return $this->vpopmail_readline(sprintf("UU %s", $email));
	}

	public function get_user_login_file($email) 
	{
		return $this->vpopmail_readline(sprintf("VV %s", $email));
	}

	public function get_global_data($domain) 
	{
		return $this->vpopmail_readline(sprintf("WW %s", $domain));
	}

	public function del_basic_baysian($domain) 
	{
		return $this->vpopmail_readline(sprintf("XX %s", $domain));
	}

	public function get_config_info($email) 
	{
		return $this->vpopmail_readline(sprintf("YY %s", $email));
	}

	public function set_global_ini($domain, $data) 
	{
		return $this->vpopmail_readline(sprintf("ZZ %s %s", $domain, $data));
	}

	public function user_addressdata_create($email, $email_list) 
	{
		return $this->vpopmail_readline(sprintf("AAA %s %s", $email, $email_list));
	}

	public function get_user_addressdata_path($email) 
	{
		return $this->vpopmail_readline(sprintf("BBB %s", $email));
	}

	public function create_basic_boxdir($email) 
	{
		return $this->vpopmail_readline(sprintf("CCC %s", $email));
	}

	public function get_user_mailbox($email) 
	{
		return $this->vpopmail_readline(sprintf("DDD %s", $email));
	}

	public function set_mxhost_config($domain, $local_email) 
	{
		return $this->vpopmail_readline(sprintf("EEE %s '%s'", $domain, $local_email));
	}

	public function modify_mxhost_alias($domain, $local_email) 
	{
		return $this->vpopmail_readline(sprintf("FFF %s '%s'", $domain, $local_email));
	}
	
	private function vpopmail_readline($opt='') 
	{
		$fp = popen(sprintf("%s %s", HMAILADMIN_PATH, $opt), 'r');
		if (!$fp) {
			return 0;
		} else {
			$vdata = '';
			while(!feof($fp)) {
				$vdata .= fgets($fp, 256);
				
			}
			pclose($fp);
			return preg_replace("/\s+$/", "", $vdata);
		}
	}
}
?>
