@extends('layouts.admin')

@section('title', 'افزودن پرامپت — وطن استودیو')

@section('content')
@include('admin.prompts.form', ['prompt' => null, 'formAction' => route('admin.prompts.store'), 'formMethod' => 'POST', 'submitLabel' => 'ثبت پرامپت'])
@endsection
