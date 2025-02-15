<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Appointment;
use App\Models\Finance;
use App\Models\Payment;
use App\Models\TimeSchedule;
use App\Models\CaseHistory;
use App\Models\Document;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Favourite;
use App\Models\department_user;
use App\Models\scheduling;
use App\Models\Review;
use Carbon\Carbon;
use App\Http\Controllers\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Hash;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function showUserprofile(Request $request)
    {
        if ((auth()->user()->type == 'patient')) {
        return view('backend.patient-dashboard')
            ->with('appointments', Appointment::where('patient_id',auth()->user()->id)->get())
            ->with('prescriptions', Prescription::where('patient_id',auth()->user()->id)->get())
            ->with('patient', User::where('id',auth()->user()->id)->first());
        }else{
            return view('backend.doctor-dashboard')
            ->with('appointments', Appointment::Where('doctor_id',auth()->user()->id)->get())
            ->with('prescriptions', Prescription::Where('doctor_id',auth()->user()->id)->get())
            ->with('doctor', User::where('id',auth()->user()->id )->first());
        }
    }

    public function editUserprofile(Request $request)
    {
        if ((auth()->user()->type == 'patient') or ($request->types == 'patient')){
        return view('backend.patient-profile-settings')->with('patient', User::where('id',$request->id)->first())->with('departments',Department::all());
        }else {
            return view('backend.doctor-profile-settings')->with('doctor', User::where('id',$request->id)->first())->with('departments',Department::all());
        }
    }
    /********************************************/
    public function showAppointmentForprofile(Request $request)
    {

        foreach (Appointment::where('patient_id',$request->id)->get() as $appointment){

            $date_time = $appointment->date.' '.$appointment->time;
            //$end_date_time = $appointment->end_date.' '.$appointment->end_time;

            //$bed = $appointment->bed;
            if (Carbon::parse($date_time)->lt(now()) && $appointment->status == 'confirmed'){
                $appointment->update([
                    'status'=> 'Treated'
                ]);
            }
            else if(Carbon::parse($date_time)->lt(now()) && $appointment->status == 'pending') {
                $appointment->update([
                    'status'=> 'cancelled'
                ]);
            }
        }
        return view('auth.profile.appointments.list')
            ->with('pendingAppointments', Appointment::where('status','pending')->where('patient_id',$request->id)->get())
            ->with('confirmedAppointments', Appointment::where('status','confirmed')->where('patient_id',$request->id)->get())
            ->with('cancelledAppointments', Appointment::where('status','cancelled')->where('patient_id',$request->id)->get())
            ->with('treatedAppointments', Appointment::where('status','treated')->where('patient_id',$request->id)->get())
            ->with('appointments', Appointment::where('patient_id',$request->id)->orWhere('doctor_id',$request->id)->get());
    }

    public function showAppointmentDetailsForPprofile(Request $request)
    {
        return view('auth.profile.appointments.show')
        ->with('doctor', User::where('id',$request->doctor_id)->first())
         ->with('patients', User::where('id',$request->patient_id)->first())
         ->with('Appointment', Appointment::where('id',$request->id)->first());
    }

    public function createAppointmentForProfile(Request $request)
    {
        if (auth()->user()->type == 'doctor') {
        return view('auth.profile.appointments.create')
            ->with('doctors', User::doctor()->get())
            ->with('patients', User::patient()->get())
            ->with('departments', department_user::where('user_id',$request->id)->join('departments','departments.id','=','department_user.department_id')->get())
            ->with('timeschedules', TimeSchedule::all());
           }   else {

                return view('auth.profile.appointments.create')
                ->with('doctors', User::doctor()->get())
                ->with('patients', User::where('id',$request->id)->get())
                ->with('departments', Department::all())
                ->with('timeschedules', TimeSchedule::all());
            }
    }
    /********************************************/
    public function showCaseHistoryForprofile(Request $request)
    {
        return view('auth.profile.casehistories.list')->with('casehistories', CaseHistory::where('patient_id',$request->id)->get());
    }

    public function showCaseHistoryDetailsForprofile(Request $request)
    {
        return view('auth.profile.casehistories.show')
        ->with('casehistory', CaseHistory::where('id',$request->caseID)->first())
        ->with('patient', User::where('id',$request->id)->get());
    }
    public function createCaseHistoriesForProfile()
    {
        return view('auth.profile.casehistories.create')
            ->with('patients', User::patient()->get());
    }
    /********************************************/
    public function showDocumentForprofile(Request $request)
    {
        return view('auth.profile.documents.list')->with('documents', Document::where('patient_id',$request->id)->orWhere('doctor_id',$request->id)->get());

    }

    public function showDoumentDetailsFor(Request $request)
    {
        return view('auth.profile.documents.show')
        ->with('document', Document::where('id',$request->id)->first() )
        ->with('patients', User::where('id',$request->patient_id)->get())
        ->with('doctors', User::where('id',$request->doctor_id)->get());

    }
    public function createDoumentDetailsFor()
    {
        return view('auth.profile.documents.create')
            ->with('patients', User::patient()->get())
            ->with('doctors', User::doctor()->get());
    }

    /********************************************/
    public function showPrescriptionForprofile(Request $request)
    {
        return view('auth.profile.prescriptions.list')->with('prescriptions', Prescription::where('id',$request->id)->get());
    }

    public function showPrescriptionDetailsForProfile(Request $request)
    {
        return view('auth.profile.prescriptions.show')
        ->with('document', Prescription::where('id',$request->id)->first() )
        ->with('patients', User::where('id',$request->patient_id)->get())
        ->with('doctors', User::where('id',$request->doctor_id)->get());
    }
    /********************************************/
    public function showTimeSchedulesForDoctor(Request $request)
    {
        return view('auth.profile.timeschedules.list')->with('doctor',User::where('id',$request->id)->first());
    }
    public function createtimeScheduleForDoctor(User $doctor)
    {
        return view('auth.profile.timeschedules.create')->with('doctor', $doctor);
    }
    /********************************************/
    public function suggestion()
    {
               return view('backend.patient-dashboard')
               ->with('appointments', Appointment::inRandomOrder()->limit(5)->where('doctor_id',auth()->user()->id)->orWhere('patient_id',auth()->user()->id )->get())
               ->with('documents', Document::inRandomOrder()->limit(5)->where('doctor_id',auth()->user()->id)->orWhere('patient_id',auth()->user()->id )->get());
    }

    /********************************************/

    public function myPatient()
    {
        $patients = DB::table('users')
            ->join('appointments', 'users.id', '=', 'appointments.patient_id')
            ->select('users.*')
            ->distinct()
            ->where('appointments.doctor_id', 1)
            ->get();
    
        return view('backend.my-patients', ['patients' => $patients]);
    }
    

    public function ShowFavourites()
    {
        return view('backend.favourites')
        ->with('favourites', Favourite::where('patient_id',auth()->user()->id)->get());
    }

   /* public function ShowProfileSettings(request $request)
    {
              $user = User::where('id',auth()->user()->id)->update($request->except('_token','_method','role'));
        return view('backend.profile-settings')->with('message','User updated successfully');
    
    }*/

    public function edit_patient()
    {
        return view('backend.profile-settings')->with('patients', User::where('id',auth()->user()->id)->get());
    
    }
    public function edit_doctor(){
        return view('backend.doctor-profile-settings')->with('doctor', User::where('id',auth()->user()->id)->get());
    }

    public function doctor_profile(Request $request){
        return view('backend.doctor-profile')->with('doctor',User::where('id',$request->id)->get()->first())
        ->with('reviews',Review::where('doctor_id',$request->id)->get());
    }

    public function review(Request $request){
        $review = new Review();
        $review->doctor_id = $request->id;
        $review->patient_id = auth()->user()->id;
        $review->comment = $request->comment;
        $review->star_rating = $request->rating;
        $review->created_at = now();
        $review->updated_at = now();
        $review->save();
        return redirect()->back()->with('flash_msg_success','Your review has been submitted Successfully,');

    }
    public function show_review(){
        return view('backend.reviews')->with('reviews',Review::where('doctor_id',auth()->user()->id)->get());
    }

    public function update(UpdateProfileRequest $request)
    {   
        $date = Carbon::createFromFormat('d/m/Y', $request->birth_date)->format('Y-m-d');
        $user = auth()->user();
        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'birth_date' => $date,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'address' => $request->address,

            'updated_at' => now()
        ]);

        session()->flash('success','Profile updated successfully!');
        return redirect()->back();
    }

    public function update_password(Request $request){
        $request->validate([
            'old_password'=>'required|min:6|max:100',
            'new_password'=>'required|min:6|max:100',
            'confirm_password'=>'required|same:new_password'
        ]);
        $current_user = auth()->user();
        if(Hash::check($request->old_password,$current_user->password)){
            $current_user->update([
                'password'=>bcrypt($request->new_password)
            ]);
            return redirect()->back()->with('success','Password seccessfully updated.');
        }else{
            return redirect()->back()->with('error','Old password does not matched.');
        }
    }

    public function change_password(){
        if(auth()->user()->type == 'patient'){
        return view('backend.change-password')->with('patient', User::where('id',auth()->user()->id)->first());
    }else{
        return view('backend.doctor-change-password')->with('doctor', User::where('id',auth()->user()->id)->first());
    }
    }
    public function prescription(){
        return view('backend.add-prescription');
    }
    public function add_prescription(){
    }
    public function patient_profile(){
        return view('backend.patient-profile');

    }
    public function social(){
        return view('backend.social-media');
    }
    public function search(Request $request){
        $str = "";
        if ($request->has('str')) {
            $str = $request->str;
        }    
        $doctors = User::where('first_name','LIKE','%'.$str.'%')->where('type','doctor')->get();
        return view('backend.search',compact('doctors'));
    }

    public function booking(Request $request){
        $app = new Appointment;
        $app->patient_id = auth()->user()->id;
        $app->doctor_id = $request->id;
        $app->status = "pending";
        $app->created_at = now();
        $app->department_id = 1;
        $app->date = now();
        $app->time = now();
        $app->save();
        return view('backend.booking-success')->with('doctor',User::where('id',$request->id)->first());
    }

    public function scheduling(Request $request){
        $user = auth()->user();
        $schedule = new scheduling();
        $schedule->insert([
            'doctor_id'=> auth()->user()->id,
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time
        ]);
        session()->flash('success','time schedule added successfully!');
        return redirect()->back();
    }

    public function appointments_cancel(Request $request){
        DB::table('appointments')
                ->where('doctor_id', auth()->user()->id)
                ->where('patient_id',$request->id)
                ->where('created_at',$request->created_at)
                ->update(['status' => 'cancelled']);
                session()->flash('success','Profile updated successfully!');
                return redirect()->back();
    }

    public function appointments_accept(Request $request){
        DB::table('appointments')
                ->where('doctor_id', auth()->user()->id)
                ->where('patient_id',$request->id)
                ->where('created_at',$request->created_at)
                ->update(['status' => 'confirmed']);
                session()->flash('success','Profile updated successfully!');
                return redirect()->back();
    }

    public function appointments()
    {
        return view('backend.appointments')->with('appointments',Appointment::where('doctor_id',auth()->user()->id)->where('status','pending')->get());
    }

}
