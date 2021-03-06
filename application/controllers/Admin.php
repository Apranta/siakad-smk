<?php 

class Admin extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
        $this->data['user_id']  = $this->session->userdata('user_id');
        if (!isset($this->data['user_id']))
        {
            $this->session->sess_destroy();
            $this->flashmsg('Anda harus login untuk mengakses halaman tersebut', 'danger');
            redirect('login');
        }

        $this->data['role_id'] = $this->session->userdata('role_id');
        if (!isset($this->data['role_id']) or $this->data['role_id'] != 3)
        {
            $this->session->sess_destroy();
            $this->flashmsg('Anda harus login sebagai admin untuk mengakses halaman tersebut', 'danger');
            redirect('login');
        }

		$this->module = 'admin';
	}

	public function index()
	{
		$this->data['title']	= 'Dashboard';
		$this->data['content']	= 'dashboard';
		$this->template($this->data, $this->module);
	}

    public function kepala_sekolah()
    {
        $this->load->model('Users');
        $this->load->model('Headmasters');

        $this->data['headmaster_id']   = $this->uri->segment(3);
        if (isset($this->data['headmaster_id']))
        {
            $teacher = Headmasters::with('user')->find($this->data['headmaster_id']);
            $teacher->delete();
            $this->flashmsg('Data guru berhasil dihapuskan');
            redirect('admin/kepala_sekolah');
        }

        if ($this->POST('submit'))
        {
            $user = new Users();
            $user->username     = $this->POST('username');
            $user->password     = md5($this->POST('password'));
            $user->role_id      = 4;
            $user->name         = $this->POST('name');
            $user->gender       = $this->POST('gender');
            $user->birthplace   = $this->POST('birthplace');
            $user->birthdate    = $this->POST('birthdate');
            $user->address      = $this->POST('address');
            $user->save();

            $headmaster = new Headmasters([
                'start_period'               => $this->POST('start_period'), 
                'end_period'    => $this->POST('end_period')
            ]);
            $user->headmaster()->save($headmaster);
            
            $this->flashmsg('Data guru berhasil ditambahkan');
            redirect('admin/kepala_sekolah');
        }
        $this->data['kepsek']     = Users::has('headmaster')->get();
        $this->data['title']    = 'Dashboard';
        $this->data['content']  = 'kepala_sekolah';
        $this->template($this->data, $this->module);
    }

    public function data_guru()
    {
        $this->load->model('Users');
        $this->load->model('Teachers');

        $this->data['teacher_id']   = $this->uri->segment(3);
        if (isset($this->data['teacher_id']))
        {
            $teacher = Teachers::with('user')->find($this->data['teacher_id']);
            $teacher->delete();
            $this->flashmsg('Data guru berhasil dihapuskan');
            redirect('admin/data-guru');
        }

        if ($this->POST('submit'))
        {
            $user = new Users();
            $user->username     = $this->POST('username');
            $user->password     = md5($this->POST('password'));
            $user->role_id      = 2;
            $user->name         = $this->POST('name');
            $user->gender       = $this->POST('gender');
            $user->birthplace   = $this->POST('birthplace');
            $user->birthdate    = $this->POST('birthdate');
            $user->address      = $this->POST('address');
            $user->save();

            $teacher = new Teachers([
                'nip'               => $this->POST('nip'), 
                'last_education'    => $this->POST('last_education')
            ]);
            $user->teacher()->save($teacher);
            
            $this->flashmsg('Data guru berhasil ditambahkan');
            redirect('admin/data-guru');
        }

        $this->data['guru']     = Users::has('teacher')->get();
        $this->data['title']    = 'Dashboard';
        $this->data['content']  = 'guru';
        $this->template($this->data, $this->module);
    }

    public function absensi_guru()
    {
        $this->data['beginning']    = new DateTime('2018-11-31');
        $this->data['ending']       = new DateTime(date('Y-m-d'));
        $this->data['ending']->setTime(0, 0, 1);

        $this->data['interval']     = DateInterval::createFromDateString('1 day');
        $this->data['period']       = array_reverse(iterator_to_array(new DatePeriod($this->data['beginning'], $this->data['interval'], $this->data['ending'])));
        $this->data['locale']   = [
            'Saturday'  => 'Sabtu',
            'Friday'    => 'Jumat',
            'Thursday'  => 'Kamis',
            'Wednesday' => 'Rabu',
            'Tuesday'   => 'Selasa',
            'Monday'    => 'Senin'
        ];

        $this->data['title']    = 'Absensi Guru';
        $this->data['content']  = 'absensi_guru';
        $this->template($this->data, $this->module);
    }

    public function absensi()
    {
        $this->data['date'] = $this->GET('date');
        $this->data['locale']   = [
            'Saturday'  => 'Sabtu',
            'Friday'    => 'Jumat',
            'Thursday'  => 'Kamis',
            'Wednesday' => 'Rabu',
            'Tuesday'   => 'Selasa',
            'Monday'    => 'Senin'
        ];
        $this->data['day']      = $this->data['locale'][$this->GET('day')];
        $this->data['day_en']   = $this->GET('day');

        $this->load->model('Teachers');
        $this->data['guru']     = Teachers::with(['user', 'attendance' => function($query) {
            $query->where('date', $this->data['date']);
        }])->get();

        if ($this->POST('submit'))
        {
            foreach ($this->data['guru'] as $guru)
            {
                $status = $this->POST('attendance-' . $guru->nip);
                if (!isset($status))
                {
                    continue;
                }

                $attendance = Teacher_attendances::where('teacher_id', $guru->teacher_id)
                                ->where('date', $this->data['date'])->first();
                if (!isset($attendance))
                {
                    $attendance = new Teacher_attendances();
                }
                $attendance->teacher_id         = $guru->teacher_id;
                $attendance->status             = $status;
                $attendance->date               = $this->data['date'];
                $attendance->additional_info    = $this->POST('info-' . $guru->nip);
                $attendance->save();
            }
            $this->flashmsg('Data absensi berhasil disimpan');
            redirect('admin/absensi?date=' . $this->data['date'] . '&day=' . $this->data['day_en']);
        }

        $this->data['title']    = 'Absensi ' . $this->data['day'] . ' - ' . $this->data['date'];
        $this->data['content']  = 'absensi';
        $this->template($this->data, $this->module);
    }

    public function detail_guru()
    {
        $this->data['teacher_id'] = $this->uri->segment(3);
        $this->check_allowance(!isset($this->data['teacher_id']));

        $this->load->model('Teachers');
        $this->data['guru']     = Teachers::with('user')->find($this->data['teacher_id']);
        $this->check_allowance(!isset($this->data['guru']), ['Data guru tidak ditemukan', 'danger']);

        $this->data['title']    = 'Detail Guru';
        $this->data['content']  = 'detail_guru';
        $this->template($this->data, $this->module);
    }

    public function edit_guru()
    {
        $this->data['teacher_id'] = $this->uri->segment(3);
        $this->check_allowance(!isset($this->data['teacher_id']));

        $this->load->model('Teachers');
        $this->data['guru']     = Teachers::with('user')->find($this->data['teacher_id']);
        $this->check_allowance(!isset($this->data['guru']), ['Data guru tidak ditemukan', 'danger']);

        if ($this->POST('submit'))
        {
            $user = Users::find($this->data['guru']->user_id);
            $user->name         = $this->POST('name');
            $user->gender       = $this->POST('gender');
            $user->birthplace   = $this->POST('birthplace');
            $user->birthdate    = $this->POST('birthdate');
            $user->address      = $this->POST('address');
            $user->save();

            $this->data['guru']->nip            = $this->POST('nip');
            $this->data['guru']->last_education = $this->POST('last_education');
            $this->data['guru']->save();

            $this->flashmsg('Data guru berhasil di-edit');
            redirect('admin/edit-guru/' . $this->data['guru']->teacher_id);
        }

        $this->data['title']    = 'Edit Guru';
        $this->data['content']  = 'edit_guru';
        $this->template($this->data, $this->module);
    }

    public function data_siswa()
    {
        $this->load->model('Users');
        $this->data['user_id']  = $this->uri->segment(3);
        if (isset($this->data['user_id']))
        {
            $user = Users::find($this->data['user_id']);
            $user->delete();
            $this->flashmsg('Data siswa berhasil dihapus');
            redirect('admin/data-siswa');
        }

        $this->data['users']    = Users::has('student')->get();
        $this->data['title']    = 'Data Siswa';
        $this->data['content']  = 'siswa';
        $this->template($this->data, $this->module);
    }

    public function tambah_siswa()
    {
        if ($this->POST('submit'))
        {
            $this->load->model('Users');
            $user = new Users();
            $user->username     = $this->POST('username');
            $user->password     = md5($this->POST('password'));
            $user->name         = $this->POST('name');
            $user->gender       = $this->POST('gender');
            $user->birthplace   = $this->POST('birthplace');
            $user->birthdate    = $this->POST('birthdate');
            $user->address      = $this->POST('address');
            $user->role_id      = 1;
            $user->save();

            $this->load->model('Students');
            $student = new Students();
            $student->nis                       = $this->POST('nis');
            $student->nisn                      = $this->POST('nisn');
            $student->father_name               = $this->POST('father_name');
            $student->father_job                = $this->POST('father_job');
            $student->father_address            = $this->POST('father_address');
            $student->mother_name               = $this->POST('mother_name');
            $student->mother_job                = $this->POST('mother_job');
            $student->mother_address            = $this->POST('mother_address');
            $student->representative_name       = $this->POST('representative_name');
            $student->representative_job        = $this->POST('representative_job');
            $student->representative_address    = $this->POST('representative_address');
            $student->accepted_date             = $this->POST('accepted_date');
            $student->school_origin             = $this->POST('school_origin');
            $student->sttb                      = $this->POST('sttb');
            $student->sttb_date                 = $this->POST('sttb_date');
            $student->leave_date                = $this->POST('leave_date');
            $student->leave_reason              = $this->POST('leave_reason');
            $student->leave_sttb                = $this->POST('leave_sttb');
            $student->leave_sttb_date           = $this->POST('leave_sttb_date');
            $student->skhun                     = $this->POST('skhun');
            $student->skhun_date                = $this->POST('skhun_date');

            $user->student()->save($student);
            $this->upload($student->student_id, 'assets/files/students', 'photo');
            $this->flashmsg('Data siswa berhasil ditambahkan');
            redirect('admin/data-siswa');
        }

        $this->data['title']    = 'Tambah Data Siswa';
        $this->data['content']  = 'tambah_siswa';
        $this->template($this->data, $this->module);
    }

    public function detail_siswa()
    {
        $this->data['student_id']   = $this->uri->segment(3);
        $this->check_allowance(!isset($this->data['student_id']));

        $this->load->model('Students');
        $this->data['siswa']    = Students::with('user')->find($this->data['student_id']);
        $this->check_allowance(!$this->data['siswa'], ['Data siswa tidak ditemukan', 'danger']);
        
        $this->data['title']    = 'Detail Data Siswa';
        $this->data['content']  = 'detail_siswa';
        $this->template($this->data, $this->module);
    }

    public function edit_siswa()
    {
        $this->data['student_id']   = $this->uri->segment(3);
        $this->check_allowance(!isset($this->data['student_id']));

        $this->load->model('Students');
        $this->data['siswa']    = Students::with('user')->find($this->data['student_id']);
        $this->check_allowance(!$this->data['siswa'], ['Data siswa tidak ditemukan', 'danger']);

        if ($this->POST('submit'))
        {
            $user = Users::find($this->data['siswa']->user->user_id);
            $user->name         = $this->POST('name');
            $user->gender       = $this->POST('gender');
            $user->birthplace   = $this->POST('birthplace');
            $user->birthdate    = $this->POST('birthdate');
            $user->address      = $this->POST('address');
            $user->save();

            $student = Students::find($this->data['student_id']);
            $student->nis                       = $this->POST('nis');
            $student->nisn                      = $this->POST('nisn');
            $student->father_name               = $this->POST('father_name');
            $student->father_job                = $this->POST('father_job');
            $student->father_address            = $this->POST('father_address');
            $student->mother_name               = $this->POST('mother_name');
            $student->mother_job                = $this->POST('mother_job');
            $student->mother_address            = $this->POST('mother_address');
            $student->representative_name       = $this->POST('representative_name');
            $student->representative_job        = $this->POST('representative_job');
            $student->representative_address    = $this->POST('representative_address');
            $student->accepted_date             = $this->POST('accepted_date');
            $student->school_origin             = $this->POST('school_origin');
            $student->sttb                      = $this->POST('sttb');
            $student->sttb_date                 = $this->POST('sttb_date');
            $student->leave_date                = $this->POST('leave_date');
            $student->leave_reason              = $this->POST('leave_reason');
            $student->leave_sttb                = $this->POST('leave_sttb');
            $student->leave_sttb_date           = $this->POST('leave_sttb_date');
            $student->skhun                     = $this->POST('skhun');
            $student->skhun_date                = $this->POST('skhun_date');

            $user->student()->save($student);
            $this->upload($student->student_id, 'assets/files/students', 'photo');
            $this->flashmsg('Data siswa berhasil di-edit');
            redirect('admin/edit-siswa/' . $this->data['student_id']);
        }

        $this->data['title']    = 'Edit Data Siswa';
        $this->data['content']  = 'edit_siswa';
        $this->template($this->data, $this->module);
    }

    public function data_mata_pelajaran()
    {
        $this->load->model('Lessons');
        if ($this->POST('submit'))
        {
            $lesson = new Lessons();
            $lesson->department_id  = $this->POST('department_id');
            $lesson->title          = $this->POST('title');
            $lesson->description    = $this->POST('description');
            $lesson->semester       = $this->POST('semester');
            $lesson->save();

            $this->flashmsg('Data mata pelajaran berhasil ditambahkan');
            redirect('admin/data-mata-pelajaran');
        }
        $this->load->model('Departments');
        $this->data['jurusan']  = Departments::get();
        $this->data['mapel']    = Lessons::has('department')->get();
        $this->data['title']    = 'Data Mata Pelajaran';
        $this->data['content']  = 'mapel';
        $this->template($this->data, $this->module);
    }

    public function detail_mata_pelajaran()
    {
        $this->data['lesson_id'] = $this->uri->segment(3);
        $this->check_allowance(!isset($this->data['lesson_id']));
        
        $this->load->model('Lessons');
        $this->data['mapel']    = Lessons::has('department')->find($this->data['lesson_id']);
        $this->check_allowance(!isset($this->data['mapel']), ['Data mata pelajaran tidak ditemukan', 'danger']);

        $this->data['title']    = 'Detail Mata Pelajaran';
        $this->data['content']  = 'detail_mapel';
        $this->template($this->data, $this->module);
    }

    public function edit_mata_pelajaran()
    {
        $this->data['lesson_id'] = $this->uri->segment(3);
        $this->check_allowance(!isset($this->data['lesson_id']));
        
        $this->load->model('Lessons');
        $this->data['mapel']    = Lessons::has('department')->find($this->data['lesson_id']);
        $this->check_allowance(!isset($this->data['mapel']), ['Data mata pelajaran tidak ditemukan', 'danger']);
        
        if ($this->POST('submit'))
        {
            $this->data['mapel']->department_id = $this->POST('department_id');
            $this->data['mapel']->title         = $this->POST('title');
            $this->data['mapel']->description   = $this->POST('description');
            $this->data['mapel']->semester      = $this->POST('semester');
            $this->data['mapel']->save();

            $this->flashmsg('Data mata pelajaran berhasil di-edit');
            redirect('admin/edit-mata-pelajaran/' . $this->data['mapel']->lesson_id);
        }

        $this->data['jurusan']  = Departments::get();
        $this->data['title']    = 'Edit Mata Pelajaran';
        $this->data['content']  = 'edit_mapel';
        $this->template($this->data, $this->module);
    }

    public function data_tahun_ajaran()
    {
        $this->load->model('School_years');
        if ($this->POST('submit'))
        {
            $school_year = new School_years();
            $school_year->school_year = $this->POST('school_year');
            $school_year->save();

            $this->flashmsg('Data tahun ajaran berhasil ditambahkan');
            redirect('admin/data-tahun-ajaran');
        }
        $this->data['tahun_ajaran']     = School_years::get();
        $this->data['title']            = 'Dashboard';
        $this->data['content']          = 'tahun_ajaran';
        $this->template($this->data, $this->module);
    }

    public function data_kelas()
    {
        $this->load->model('Classes');
        if ($this->POST('submit'))
        {
            $class = new Classes();
            $class->class_name = $this->POST('class_name');
            $class->save();

            $this->flashmsg('Data kelas berhasil ditambahkan');
            redirect('admin/data-kelas');
        }
        $this->data['kelas']    = Classes::get();
        $this->data['title']    = 'Data Kelas';
        $this->data['content']  = 'kelas';
        $this->template($this->data, $this->module);
    }

    public function detail_kelas()
    {
        $this->data['class_id'] = $this->uri->segment(3);
        $this->check_allowance(!isset($this->data['class_id']));

        $this->load->model('Classes');
        $this->data['kelas'] = Classes::with('homerooms', 'homerooms.year')->find($this->data['class_id']);
        $this->check_allowance(!isset($this->data['kelas']), ['Data kelas tidak ditemukan', 'danger']);

        if ($this->POST('submit'))
        {
            $homeroom = new Homerooms();
            $homeroom->teacher_id   = $this->POST('teacher_id');
            $homeroom->class_id     = $this->data['class_id'];
            $homeroom->year_id      = $this->POST('year_id');
            $homeroom->semester     = $this->POST('semester');
            $homeroom->save();

            $this->flashmsg('Data wali kelas berhasil ditambahkan');
            redirect('admin/detail-kelas/' . $this->data['class_id']);
        }

        $this->load->model('Teachers');
        $this->data['guru'] = Teachers::with('user')->get();
        $this->data['tahun_ajaran'] = School_years::get();

        $this->data['title']    = 'Detail Kelas';
        $this->data['content']  = 'detail_kelas';
        $this->template($this->data, $this->module);
    }

    public function tahun_anggota_kelas()
    {
        $this->data['class_id'] = $this->uri->segment(3);
        $this->check_allowance(!isset($this->data['class_id']));

        $this->load->model('Classes');
        $this->data['kelas']    = Classes::find($this->data['class_id']);
        $this->check_allowance(!isset($this->data['kelas']), ['Data kelas tidak ditemukan', 'danger']);        

        $this->load->model('School_years');
        $this->data['tahun_ajaran'] = School_years::get();
        $this->data['title']    = 'Tahun Anggota Kelas';
        $this->data['content']  = 'tahun_anggota_kelas';
        $this->template($this->data, $this->module);
    }

    public function anggota_kelas()
    {
        $this->data['class_id'] = $this->GET('class_id');
        $this->data['year_id']  = $this->GET('year_id');

        $this->check_allowance(!isset($this->data['class_id']));

        $this->load->model('Classes');
        $this->data['kelas']    = Classes::find($this->data['class_id']);
        $this->check_allowance(!isset($this->data['kelas']), ['Data kelas tidak ditemukan', 'danger']);        

        $this->load->model('School_years');
        $this->data['tahun_ajaran'] = School_years::find($this->data['year_id']);
        $this->check_allowance(!isset($this->data['tahun_ajaran']), ['Data tahun ajaran tidak ditemukan', 'danger']);

        $this->load->model('Class_members');
        $this->data['member_id'] = $this->GET('member_id');
        if (isset($this->data['member_id']))
        {
            $member = Class_members::find($this->data['member_id']);
            $member->delete();
            $this->flashmsg('Data anggota kelas berhasil dihapuskan');
            redirect('admin/anggota-kelas?class_id=' . $this->data['class_id'] . '&year_id=' . $this->data['year_id']);
        }

        if ($this->POST('submit'))
        {
            $member = Class_members::where('class_id', $this->data['class_id'])
                        ->where('year_id', $this->data['year_id'])
                        ->where('student_id', $this->POST('student_id'))
                        ->first();
            if (!isset($member))
            {
                $member = new Class_members();
            }
            $member->student_id = $this->POST('student_id');
            $member->class_id   = $this->data['class_id'];
            $member->year_id    = $this->data['year_id'];
            $member->save();

            $this->flashmsg('Data anggota kelas berhasil ditambahkan');
            redirect('admin/anggota-kelas?class_id=' . $this->data['class_id'] . '&year_id=' . $this->data['year_id']);
        }

        $this->data['anggota_kelas'] = Classes::with(['members' => function($query) {
            $query->where('year_id', $this->data['year_id']);
        }])->find($this->data['class_id']);

        $this->load->model('Students');
        $this->data['siswa']    = Students::with('user')->get();

        $this->data['title']    = 'Anggota Kelas ' . $this->data['kelas']->class_name . ' Tahun Ajaran ' . $this->data['tahun_ajaran']->school_year;
        $this->data['content']  = 'anggota_kelas';
        $this->template($this->data, $this->module);
    }

    public function data_jadwal()
    {
        $this->load->model('Schedules');
        if ($this->POST('submit'))
        {
            $schedule = new Schedules();
            $schedule->class_id     = $this->POST('class_id');
            $schedule->lesson_id    = $this->POST('lesson_id');
            $schedule->teacher_id   = $this->POST('teacher_id');
            $schedule->year_id      = $this->POST('year_id');
            $schedule->semester     = $this->POST('semester');
            $schedule->started_at   = $this->POST('started_at');
            $schedule->ended_at     = $this->POST('ended_at');
            $schedule->save();

            $this->flashmsg('Data jadwal pelajaran berhasil ditambahkan');
            redirect('admin/data-jadwal');
        }

        $this->load->model('Classes');
        $this->load->model('Lessons');
        $this->load->model('Teachers');
        $this->load->model('School_years');

        $this->data['kelas']        = Classes::get();
        $this->data['mapel']        = Lessons::get();
        $this->data['guru']         = Teachers::get();
        $this->data['tahun_ajaran'] = School_years::get();
        $this->data['jadwal']       = Schedules::with('class', 'lesson', 'teacher', 'year')->get();
        $this->data['title']        = 'Dashboard';
        $this->data['content']      = 'jadwal';
        $this->template($this->data, $this->module);
    }

    public function data_penilaian()
    {
        $this->load->model('Score_types');
        if ($this->POST('submit'))
        {
            $score_type = new Score_types();
            $score_type->type_name      = $this->POST('type_name');
            $score_type->description    = $this->POST('description');
            $score_type->percentage     = $this->POST('percentage');
            $score_type->save();   

            $this->flashmsg('Data penilaian berhasil ditambahkan');
            redirect('admin/data-penilaian');
        }
        $this->data['penilaian']    = Score_types::get();
        $this->data['title']        = 'Data Penilaian';
        $this->data['content']      = 'penilaian';
        $this->template($this->data, $this->module);
    }

    public function data_jurusan()
    {
        $this->load->model('Departments');
        if ($this->POST('submit'))
        {
            $department = new Departments();
            $department->department_name    = $this->POST('department_name');
            $department->description        = $this->POST('description');
            $department->save();

            $this->flashmsg('Data jurusan berhasil ditambahkan');
            redirect('admin/data-jurusan');
            exit;
        }

        $this->data['jurusan']  = Departments::get();
        $this->data['title']    = 'Dashboard';
        $this->data['content']  = 'jurusan';
        $this->template($this->data, $this->module);
    }

    public function laporan_nilai()
    {
        $this->data['title']    = 'Dashboard';
        $this->data['content']  = 'nilai';
        $this->template($this->data, $this->module);
    }

    public function visi_misi()
    {
        $this->load->model('Headmasters');
        $this->data['headmaster']   = Headmasters::orderBy('start_period', 'DESC')
                                        ->get()
                                        ->first();
        $this->data['title']        = 'Visi Misi';
        $this->data['content']      = 'visimisi';
        $this->template($this->data, $this->module);
    }

}