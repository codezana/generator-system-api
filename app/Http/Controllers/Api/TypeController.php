<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Types;
use Illuminate\Support\Facades\Validator;

class TypeController extends Controller
{
     /**
    * Display a listing of the resource.
    */

    public function index() {
        
        $types = Types::all();
        return response()->json( $types );
    }

    /**
    * Store a newly created resource in storage.
    */

    public function store( Request $request ) {
        $validator = Validator::make( $request->all(), [
            'name' => 'required|string|max:100',
        ] );

        if ( $validator->fails() ) {
            return response()->json( [
                'error' => collect( $validator->errors()->all() )->first()
            ], 422 );
        }

        $type = Types::create( [
            'name' => $request->input( 'name' ),
        ] );

        return response()->json( [ 'message' => 'بەسەرکەوتوویی جۆری خەرجی دروستکرا', 'data' => $type ] );
    }

    /**
    * Display the specified resource.
    */

    public function show($id ) {
        
        $type = Types::findOrFail( $id );
        return response()->json( $type );
    }


    /**
    * Update the specified resource in storage.
    */

    public function update( Request $request,$id ) {
        

        $validator = Validator::make( $request->all(), [
            'name' => 'string|max:100',
        ]);

        if ( $validator->fails() ) {
            return response()->json( [
                'error' => collect( $validator->errors()->all() )->first()
            ], 422 );
        }
       try {
        $type = Types::findOrFail( $id );
        $type->name = $request->input( 'name' ) ?? $type->name;
        $type->save();

        return response()->json( [ 'message' => 'بەسەرکەوتوویی جۆری خەرجی نوێکرایەوە', 'data' => $type ] );

       } catch (\Exception $e) {
        return response()->json( [ 
            'error' => 'کێشەیەک ڕویدا لە نوێکردنەوەی جۆری خەرجی'
            , 'data' => $e->getMessage()], 500 );
       }
        
    }

    /**
    * Remove the specified resource from storage.
    */

    public function destroy($id) {

            $type = Types::findOrFail( $id );
            $type->delete();
            return response()->json( [ 'message' => 'بەسەرکەوتوویی جۆری خەرجی سڕایەوە' ] );
    }
}
