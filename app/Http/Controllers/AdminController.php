<?php

namespace App\Http\Controllers;

use App\Helpers;
use App\Models\Prodi;
use App\Models\Room;
use App\Models\Semester;
use App\Models\Admin;
use App\Models\Users;
use App\Models\WaktuKuliah;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Kalender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function __construct()
    {
        //attach auth middleware, if user not authenticated forward to login page
        //$this->middleware('auth');
    }

    public function profiladmin()
    {
        $user = Auth::user();
        $admin = $user->admin;
        return view('admin.profiladmin', ['admin' => $admin]);
    }

    public function updateProfilAdmin(Request $request){
        $input = $request->all();

        $validator = $this->profile_validator($input);
        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        }

        $admin = Admin::find($input['id']);
        $admin->Nama_Admin = $input['Nama_Admin'];
        $admin->Alamat = $input['Alamat'];
        $admin->Telepon = $input['Telepon'];
        $admin->save();
        return $this->profiladmin();
    }

    public function AjaxReloadRekapAdminWithSemester(Request $request){
        //get list of semester
        $input=$request->all();

        $semester_id=$request['Semester'];
        $response = array(
            'response' => 'Called created successfully',
            '_token'=>$input['_token'],
            'Semester'=>$semester_id
        );

        //the first call to respond selectChange goes to here
        if (!array_key_exists('step',$input)){
            return response()->json($response);
        }

        //the second call to respond body.onload() from ajax goes here
        $kalender = Kalender::all('id','semester');;
        $kalender_array = Helpers::modelAsAssociativeArray($kalender,'id','semester');

        //get list of course sections for current semester and lecturer
        //$lecturer = Auth::user()->lecturer;
        //$kode_dosen = $lecturer->Kode_Dosen;
        $course_model = DB::table('courses')
            ->where('id_semester',$semester_id);
        $course = $course_model->get();
        $course_array = Helpers::modelAsAssociativeArray($course,'seksi','Nama_Matkul');
        //make default showing first course on the list
        if(empty($course)) {
            $enrollment = array();
            $seksi=null;
        } else{
            $seksi=$course[0]->seksi;
            //pass along list of enrolled students
            $enrollment=$course_model
                ->where('seksi',$seksi)
                ->join('enrollments as e','e.kode_seksi','=','courses.seksi')
                ->join('students as s','s.Noreg','=','e.noreg')
                ->get();
        }

        return view('admin.rekap')
            ->with('kalender_options',$kalender_array)
            ->with('course_options',$course_array)
            ->with('course',$course)
            ->with('enrolls',$enrollment)
            ->with('semester_id',$semester_id)
            ->with('kode_seksi',$seksi);
    }

    public function postRekapAdmin(Request $request){
        //get list of semester
        $kalender = Kalender::all('id','semester');;
        $kalender_array = Helpers::modelAsAssociativeArray($kalender,'id','semester');

        //get list of course sections for current semester and lecturer
        //$lecturer = Auth::user()->lecturer;
        //$kode_dosen = $lecturer->Kode_Dosen;

        $semester = $request->input('Semester');
        //for debugging
        $course_model = DB::table('courses')
            ->where('id_semester',$semester);
        $course = $course_model->get();
        $course_array = Helpers::modelAsAssociativeArray($course,'seksi','Nama_Matkul');
        //make default showing first course on the list
        if(empty($course)) {
            $enrollment = array();
        } else{
            $seksi=$request->input('Kode_Seksi');
            //pass along list of enrolled students
            $enrollment=$course_model
                ->where('seksi',$seksi)
                ->join('enrollments as e','e.kode_seksi','=','courses.seksi')
                ->join('students as s','s.Noreg','=','e.noreg')
                ->get();
        }

        return view('admin.rekap')
            ->with('kalender_options',$kalender_array)
            ->with('course_options',$course_array)
            ->with('course',$course)
            ->with('enrolls',$enrollment)
            ->with('semester_id',$semester)
            ->with('kode_seksi',$seksi);
    }

    public function rekapAdmin(Request $request){
        //get list of semester
        $kalender = Kalender::all('id','semester');
        $kalender_array = Helpers::modelAsAssociativeArray($kalender,'id','semester');

        //get list of course sections for current semester and lecturer
        //$lecturer = Auth::user()->lecturer;
        //$kode_dosen = $lecturer->Kode_Dosen;

        $semester = Kalender::getRunningSemester();
        $id_semester = (empty($semester) ? 0 : $semester->id);
        //for debugging
        $course_model = DB::table('courses')
            ->where('id_semester',$id_semester);
        $course = $course_model->get();
        $course_array = Helpers::modelAsAssociativeArray($course,'seksi','Nama_Matkul');
        //make default showing first course on the list
        $enrollment=array();
        $seksi=array();
        if(empty($course) || $course->count()==0) {
            $enrollment = array();
            $seksi = array();

        } else{
            $seksi=$course[0]->seksi;
            //pass along list of enrolled students
            $enrollment=$course_model
                ->where('seksi',$seksi)
                ->join('enrollments as e','e.kode_seksi','=','courses.seksi')
                ->join('students as s','s.Noreg','=','e.noreg')
                ->get();
        }

        return view('admin.rekap')
            ->with('kalender_options',$kalender_array)
            ->with('course_options',$course_array)
            ->with('course',$course)
            ->with('enrolls',$enrollment)
            ->with('semester_id',$id_semester)
            ->with('kode_seksi',$seksi);
    }


    public function ajaxreloadshowadminwithsemester(Request $request){
        //get list of semester
        $input=$request->all();

        $semester_id=$request['Semester'];
        $response = array(
            'response' => 'Called created successfully',
            '_token'=>$input['_token'],
            'Semester'=>$semester_id
        );

        //the first call to respond selectChange goes to here
        if (!array_key_exists('step',$input)){
            return response()->json($response);
        }

        //the second call to respond body.onload() from ajax goes here
        $kalender = Kalender::all('id','semester');;
        $kalender_array = Helpers::modelAsAssociativeArray($kalender,'id','semester');

        //get list of course sections for current semester and lecturer
        //$lecturer = Auth::user()->lecturer;
        //$kode_dosen = $lecturer->Kode_Dosen;
        $course_model = DB::table('courses')
            ->where('id_semester',$semester_id);
        $course = $course_model->get();
        $course_array = Helpers::modelAsAssociativeArray($course,'seksi','Nama_Matkul');
        //make default showing first course on the list
        if(empty($course)) {
            $enrollment = array();
            $seksi=null;
        } else{
            $seksi=$course[0]->seksi;
            //pass along list of enrolled students
            $enrollment=$course_model
                ->where('seksi',$seksi)
                ->join('enrollments as e','e.kode_seksi','=','courses.seksi')
                ->join('students as s','s.Noreg','=','e.noreg')
                ->get();
        }

        return view('admin.showadmin')
            ->with('kalender_options',$kalender_array)
            ->with('course_options',$course_array)
            ->with('course',$course)
            ->with('enrolls',$enrollment)
            ->with('semester_id',$semester_id)
            ->with('kode_seksi',$seksi);
    }

    public function postshowadmin(Request $request){
        //get list of semester
        $kalender = Kalender::all('id','semester');;
        $kalender_array = Helpers::modelAsAssociativeArray($kalender,'id','semester');

        //get list of course sections for current semester and lecturer
        //$lecturer = Auth::user()->lecturer;
        //$kode_dosen = $lecturer->Kode_Dosen;

        $semester = $request->input('Semester');
        //for debugging
        $course_model = DB::table('courses')
            ->where('id_semester',$semester);
        $course = $course_model->get();
        $course_array = Helpers::modelAsAssociativeArray($course,'seksi','Nama_Matkul');
        //make default showing first course on the list
        if(empty($course)) {
            $enrollment = array();
        } else{
            $seksi=$request->input('Kode_Seksi');
            //pass along list of enrolled students
            $enrollment=$course_model
                ->where('seksi',$seksi)
                ->join('enrollments as e','e.kode_seksi','=','courses.seksi')
                ->join('students as s','s.Noreg','=','e.noreg')
                ->get();
        }

        return view('admin.showadmin')
            ->with('kalender_options',$kalender_array)
            ->with('course_options',$course_array)
            ->with('course',$course)
            ->with('enrolls',$enrollment)
            ->with('semester_id',$semester)
            ->with('kode_seksi',$seksi);
    }

    public function showadmin(Request $request){
        //get list of semester
        $kalender = Kalender::all('id','semester');
        $kalender_array = Helpers::modelAsAssociativeArray($kalender,'id','semester');

        //get list of course sections for current semester and lecturer
        //$lecturer = Auth::user()->lecturer;
        //$kode_dosen = $lecturer->Kode_Dosen;

        $semester = Kalender::getRunningSemester();
        $id_semester = (empty($semester) ? 0 : $semester->id);
        //for debugging
        $course_model = DB::table('courses')
            ->where('id_semester',$id_semester);
        $course = $course_model->get();
        $course_array = Helpers::modelAsAssociativeArray($course,'seksi','Nama_Matkul');
        //make default showing first course on the list
        $enrollment=array();
        $seksi=array();
        if(!empty($course) && $course->count()>0) {
            $seksi=$course[0]->seksi;
            //pass along list of enrolled students
            $enrollment=$course_model
                ->where('seksi',$seksi)
                ->join('enrollments as e','e.kode_seksi','=','courses.seksi')
                ->join('students as s','s.Noreg','=','e.noreg')
                ->get();
        }

        return view('admin.showadmin')
            ->with('kalender_options',$kalender_array)
            ->with('course_options',$course_array)
            ->with('course',$course)
            ->with('enrolls',$enrollment)
            ->with('semester_id',$id_semester)
            ->with('kode_seksi',$seksi);
    }


/*    public function showadmin()
    {
        return view('admin.showadmin');
    }*/

    public function postcrudjadwal(Request $request)
    {
        $kalender = Kalender::all('id','semester');;
        $kalender_array = Helpers::modelAsAssociativeArray($kalender,'id','semester');
        $semester = $request->input('Semester');
        //$semester = Kalender::getRunningSemester();
        //$id_semester = Kalender::getRunningSemester()->id;
        $courses = Course::where('id_semester',$semester)
            ->get();
        return view('admin.crudjadwal')
            ->with('Courses', $courses)
            ->with('kalender_options',$kalender_array)
            ->with('semester_id',$semester);
    }
    public function crudjadwal()
    {
        $kalender = Kalender::all('id','semester');;
        $kalender_array = Helpers::modelAsAssociativeArray($kalender,'id','semester');
        $semester = Kalender::getRunningSemester();
        $id_semester = (empty($semester) ? 0 : $semester->id);
        $courses = Course::where('id_semester',$id_semester)
            ->get();
        return view('admin.crudjadwal')
            ->with('Courses', $courses)
            ->with('kalender_options',$kalender_array)
            ->with('semester_id',$id_semester);
    }
    public function addjadwal()
    {
        $prodis = Prodi::all();
        $prodi_arr=array();
        foreach($prodis as $prodi){
            $prodi_arr[$prodi->id]=$prodi->prodi;
        }
        $room = Room::room_name();
        $room_arr = Helpers::toAssociativeArrays($room);
        $waktu_kuliah = WaktuKuliah::waktuMap();
        $waktu_opts = Helpers::toAssociativeArrays($waktu_kuliah);
        //Todo: Must offer all dosen data here, at the moment post not implemented
        $Kode_Dosen = null;
        return view('admin.addjadwal')
            ->with('prodi_options',$prodi_arr)
            ->with('room_options', $room_arr)
            ->with('waktu_options', $waktu_opts)
            ->with('Kode_Dosen', $Kode_Dosen);
    }
    public function editjadwal()
    {

    }
    public function savejadwal()
    {
        
        return view('admin.addjadwal');
    }
    public function deletejadwal()
    {

    }

    public function viewuser()
    {
        //get all user instance
        $users = DB::table('users')->paginate(20);
        return view('admin.viewuser')->with('users',$users);
    }

    public function viewSemester(){
        $semesters = Semester::all();
        return view('admin.viewsemester')->with('semesters',$semesters);
    }

    public function addSemester(){
        return view('admin.addsemester');
    }

    public function saveSemester(Request $request){
        $validator = $this->semester_validator($request->all());
        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        }

        $semester = new Semester;
        $semester->semester = $request['semester'];
        $semester->start_period = $request['start_period'];
        $semester->end_period = $request['end_period'];
        $semester->active = 1;
        $semester->save();

        return redirect('/admin/view_semester');
    }

    public function updateSemester(Request $request){
        $validator = $this->semester_validator($request->all());
        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        }

        $id=$request['id'];
        $semester=Semester::find($id);
        $semester->semester = $request['semester'];
        $semester->start_period = $request['start_period'];
        $semester->end_period = $request['end_period'];
        $semester->active = $request['active'];
        $semester->save();

        return $this->viewSemester();
    }

    public function editUser(Request $request){
        $validator = $this->account_validator($request->all());
        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        }

        //mistake: need to get currently edited user
        $user = Users::find($request['id']);
        $user->name=$request['name'];
        $user->email=$request['email'];
        $user->password=Hash::make($request['password']);
        $user->save();
        return $this->viewuser();
    }

    //Todo: Finishing implementation for delete user
    public function deleteUser(Request $request){
        $user = Users::find($request['id']);
        $user->name=$request['name'];
        $user->email=$request['email'];
        $user->password=Hash::make($request['password']);
        $user->delete();
        return $this->viewuser();
    }

    protected function semester_validator(array $data)
    {
        $validator = Validator::make($data, [
            'semester' => 'required|max:255',
            'start_period' => 'required',
            'end_period' => 'required',
            'active' => 'boolean',
        ]);

        return $validator;
    }


    protected function profile_validator(array $data){
        $validator =Validator::make($data, [
            'Telepon' => 'numeric',
        ]);
        return $validator;
    }

    protected function account_validator(array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|confirmed|min:6',
        ]);

        /*$func = function($validator) {
            //but we can debug if anonymous function calls another function
            if ($this->check_password($validator)) {
                $validator->errors()->add('field', 'Old password do not match database record!');
            }
        };
        $validator->after($func);
        */
        return $validator;
    }

    /**
     * Unused now, previously used to reset password in admin by verifying password but wrong business case
     * @param $validator
     * @return bool
     */
    protected function check_password($validator){
        //get current user id
        $user = Auth::user();
        $old_password = $validator->getData()['old_password'];
        $db_password = $user->password;
        if(Hash::check($old_password,$db_password))
            return false;
        return true;
    }
}
