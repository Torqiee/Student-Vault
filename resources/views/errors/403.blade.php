@extends('errors.layout')

@section('status', '403')
@section('icon', 'fa-solid fa-lock')
@section('headline', 'This page is private')
@section('message', 'You don’t have permission to open this page. Return to your workspace to continue securely.')
