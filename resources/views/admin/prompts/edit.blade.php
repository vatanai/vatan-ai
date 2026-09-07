@extends('layouts.admin')

@section('title', 'ویرایش پرامپت — وطن استودیو')

@section('content')
@include('admin.prompts.form', ['prompt' => $prompt, 'formAction' => route('admin.prompts.update', $prompt->id), 'formMethod' => 'PUT', 'submitLabel' => 'ذخیره تغییرات'])
@endsection
