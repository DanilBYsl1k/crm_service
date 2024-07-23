<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRequest;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function upload(UploadRequest $request)
    {
        $data = $request->validated();

        $path = Storage::disk('public')->put('/images', $data['image']);

//        $image = Image::create([
//            'path' => $path,
//            'user_id' => auth()->id(),
//        ]);

//        return imf
    }
}
