<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResponseCollection;
use App\Models\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class UploaderController extends Controller
{
    public static function rules()
    {
        return [
            'image' => [
                'file' => 'required|image|max:10000'
            ]
        ];
    }

    public function uploaderImage($req_image) {
        try {
            $original = 'uploader/original/';
            $thumbnail = 'uploader/thumbnail/';

            $fileName = now()->format('Y-m-d_H-i-s').'_'.$req_image->getClientOriginalName();
            $extension = $req_image->getClientOriginalExtension();
            $size = $req_image->getSize();
            Image::make($req_image->getRealPath())->resize(null, 250, function ($constraint) {
                $constraint->aspectRatio();
            })->save(public_path($thumbnail).$fileName);
            $req_image->move(public_path($original), $fileName);
            $url = URL::asset($original.$fileName);
            $url_thumbnail = URL::asset($thumbnail.$fileName);

            $uploader = new Uploader;
            $uploader->name = $fileName;
            $uploader->extension = $extension;
            $uploader->size = $size;
            $uploader->url = $url;
            $uploader->thumbnail = $url_thumbnail;
            $uploader->save();

            return ['data' => $uploader, 'status' => true];
        } catch (\Exception $e) {
            return ['data' => $e->getMessage(), 'status' => false];
        }
    }
    public function image(Request $request) {
        $validator      = Validator::make($request->all(), self::rules()['image']);
        $errors         = array_values($validator->errors()->all());
        if ($errors) {
            return (new GeneralResponseCollection([], $errors, true))
                ->response()->setStatusCode(400);
        }
        $uploader = self::uploaderImage($request->file('file'));
        if ($uploader['status']) {
            return (new GeneralResponseCollection($uploader['data'], ['Success upload image'], true))
                ->response()->setStatusCode(200);
        } else {
            return (new GeneralResponseCollection([], ['failed to upload file'], false))
                ->response()->setStatusCode(400);
        }
    }

    public function wysiwyg(Request $request) {
        $validator      = Validator::make($request->all(), self::rules()['image']);
        $errors         = array_values($validator->errors()->all());
        if ($errors) {
            return (new GeneralResponseCollection([], $errors, true))
                ->response()->setStatusCode(400);
        }
        $uploader = self::uploaderImage($request->file('file'));
        if ($uploader['status']) {
            return response($uploader['data']['url'],200)->withHeaders(['content-type' => 'text/html']);
        } else {
            return (new GeneralResponseCollection([], ['failed to upload file'], false))
                ->response()->setStatusCode(400);
        }
    }
}
