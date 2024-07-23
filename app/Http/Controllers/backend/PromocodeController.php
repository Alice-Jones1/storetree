<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promocode;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;






class PromocodeController extends BaseController
{
    public function __construct() {
        parent::__construct();
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        return view('backend.promocode.create');
    }

   



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $promocodes = Promocode::where('id', '>', 0);
        
        $promocodes = $promocodes->sortable(['sort' => 'ASC'])->paginate(50);
// dd($promocodes);
        return view('backend.promocode.index', compact('promocodes'));
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $rules = [
            'code' => ['required', Rule::unique('promocodes', 'code')->whereNull('deleted_at'), ],
            'type' => ['required'],
            'discount' => ['required','integer','digits_between:1,100'],

        ];

        $messages = [
           'code.required' => 'Please Enter the PromoCode Value',
           'code.unique' => 'This PromoCode is already taken',
           'type.required' => 'Please Select Promocode Type',
           'discount.required' => 'Please Enter the Discount Value',
           'discount.integer' => 'Discount Value Must be a Number',
           'discount.digits_between' => 'The discount value must be between 1% and 99%'

        ];

        $this->validate($request, $rules, $messages);
   

        $input = $request->all();
        $input['admin_id'] = Auth::guard('admin')->user()->id;
        if (!$request->get('sort')) {
            $input['sort'] = Promocode::max('id') + 1;
        }
        
        Promocode::create($input);
        Session::flash('success', 'The Promocode has been created');

        return redirect()->route('admin.promocodes.index');
    }




    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $promocode = Promocode::find($id);
        return view('backend.promocode.edit', compact('promocode'));
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
          
        $rules = [
            'code' => ['required', Rule::unique('promocodes', 'code')->whereNull('deleted_at')->ignore($id), ],
            'type' => ['required'],
            'discount' => ['required','integer','digits_between:1,100'],
            'expiry' => ['required'],
        ];

        $messages = [
           'code.required' => 'Please Enter the PromoCode Value',
           'code.unique' => 'This PromoCode is already taken',
           'type.required' => 'Please Select Promocode Type',
           'discount.required' => 'Please Enter the Discount Value',
           'discount.integer' => 'Discount Value Must be a Number',
           'discount.digits_between' => 'The discount value must be between 1% and 99%',
           'expiry.required' => 'Please Enter the Expiry Date',
        ];

        $this->validate($request, $rules, $messages);


        $input = $request->all();

        $input['admin_id'] = Auth::guard('admin')->user()->id;
        
        $promocode = Promocode::find($id);
        if (!$request->get('sort')) {
            $input['sort'] = $promocode->sort;
        }

        if($promocode->status == 'expired'){
                $promocode->status = 'active';
        }

        $promocode->update($input);
        Session::flash('success', 'The Promocode has been updated');

        return redirect()->route('admin.promocodes.index');
    }



     /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $promocode = Promocode::find($id);
        $message =  'The Promocode "'.$promocode->code.'" has been deleted' ;
        $promocode->update([
            'admin_id' => Auth::guard('admin')->user()->id
        ]);
        $promocode->delete();
        Session::flash('success', $message);
        return redirect()->back();
    }
}
