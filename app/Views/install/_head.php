<?php /** @var string $pageTitle */ ?><!DOCTYPE html>
<html lang="en-ZA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($pageTitle ?? 'Install') ?> — SARCNA 2027 Convention</title>
<link rel="icon" href="/assets/brand/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css">
<style>
  body { background: linear-gradient(180deg, #173D2F 0%, #0E241C 100%); min-height: 100vh; }
  .install { width: min(100% - 2rem, 880px); margin: 2.5rem auto 4rem; }
  .install__card { background: var(--surface-raised); border-radius: var(--radius-xl); padding: clamp(1.5rem, 4vw, 3rem); box-shadow: var(--shadow-l); }
  .install__brand { text-align: center; margin-bottom: 2rem; }
  .install__brand img { height: 80px; margin: 0 auto .75rem; }
  .install__brand p { color: rgba(255,246,231,.75); font-size: var(--step--1); margin: 0 auto; }
  .check { display: flex; gap: .75rem; align-items: flex-start; padding: .55rem 0; border-bottom: 1px solid var(--line); font-size: var(--step--1); }
  .check:last-child { border-bottom: 0; }
  .check__mark { width: 22px; height: 22px; flex-shrink: 0; border-radius: 50%; display: grid; place-items: center; font-size: .75rem; font-weight: 700; }
  .check__mark.ok { background: rgba(47,125,79,.16); color: var(--success); }
  .check__mark.no { background: rgba(184,64,58,.14); color: var(--error); }
  .install fieldset { background: var(--surface-sunk); }
  code { background: var(--surface-sunk); padding: .15rem .4rem; border-radius: 4px; font-size: .875em; }
  pre { background: var(--charcoal); color: var(--mist); padding: 1rem 1.25rem; border-radius: var(--radius-m); overflow-x: auto; font-size: .82rem; line-height: 1.6; }
</style>
</head>
<body>
<div class="install">
  <div class="install__brand">
    <img src="/assets/brand/logo-light.svg" alt="SARCNA 2027 Convention">
    <p>Rooted in Recovery. Rising Together.</p>
  </div>
  <div class="install__card">
