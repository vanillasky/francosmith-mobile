<?php
/*
	Session Ŭ����
*/
class session {

	var $m_no;
	var $m_id;
	var $level;
	var $groupsno;
	var $dc;

	function session() {
		if($_SESSION['sess']['m_no']) {
			$this->m_no=$_SESSION['sess']['m_no'];
			$this->m_id=$_SESSION['sess']['m_id'];
			$this->level=$_SESSION['sess']['level'];
			$this->groupsno=$_SESSION['sess']['groupsno'];
			$this->dc=$_SESSION['sess']['dc'];
		}
		else {
			$this->m_no=false;
			$this->m_id='';
			$this->level='';
			$this->groupsno='';
			$this->dc='';
		}
	}

	/*
		�α���

		return ��
		���������� �α��� �� ��� = true
		���̵� ��й�ȣ�� �Է����Ŀ� ��߳� ��� = NOT_VALID
		���̵� ��й�ȣ ���� �ʴ� ��� = NOT_FOUND
		������ ���ε��� �ʴ� ��� = NOT_ACCESS
	*/
	function login($id,$password) {
		// �Է� ���� üũ
		$validation_check = array(
			'id'=>array('require'=>true,'pattern'=>'/^[\xa1-\xfea-zA-Z0-9_-]{4,20}$/'),
			'password'=>array('require'=>true,'pattern'=>'/^[\x21-\x7E]{4,}$/'),
		);
		$chk_result = array_value_cheking($validation_check,array('id'=>$id,'password'=>$password));
		if(count($chk_result)) {
			return 'NOT_VALID';
		}

		// ���̵�,��й�ȣ ��ȸ
		GLOBAL $db;
		$query = $db->_query_print('
			select
				m.m_no,
				m.m_id,
				m.name,
				m.nickname,
				m.email,
				m.status,
				m.level,
				g.dc,
				g.sno gsno
			from
				gd_member as m
				left join gd_member_grp as g on m.level=g.level
			where
				m.m_id = [s] and
				m.password in (password([s]),old_password([s]),[s])
		',$id,$password,$password,md5($password));

		$result = $db->_select($query);
		$result = $result[0];

		if(!$result['m_no']) { // ��ġ�ϴ� ��� ���� ���� ���
			return 'NOT_FOUND';
		}

		if($result['status']==1)  { // �α����� ������ ���
			// �α��� ������ ���� �ֱ� �α��γ�¥ ����
			$query = $db->_query_print('
				update gd_member set
					last_login = now(),
					cnt_login = (cnt_login+1),
					last_login_ip = [s]
				where
					m_no = [s]
			',$_SERVER['REMOTE_ADDR'],$result['m_no']);
			$db->_query($query);

			// �������� ����
			$_SESSION['sess']=array(
				'm_no'=>$result['m_no'],
				'm_id'=>$result['m_id'],
				'level'=>$result['level'],
				'groupsno'=>$result['gsno'],
				'dc'=>($result['dc'] ? $result['dc'].'%' : '')
			);
			$_SESSION['member']=array(
				'name'=>$result['name'],
        'email'=>$result['email'],
        'nickname'=>$result['nickname'],
			);
			$this->session();
			return true;
		}
		else {
			return 'NOT_ACCESS';
		}

	}


	/*
		�α׾ƿ�
	*/
	function logout() {
		session_destroy();
	}

}


?>