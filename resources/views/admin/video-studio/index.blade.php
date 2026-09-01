@extends('layouts.admin')

@section('title', 'تولید خودکار ویدیو — وطن استودیو')

@push('styles')
<style>
  .video-studio-page{background:var(--page-bg);min-height:calc(100vh - 68px);padding:24px;direction:rtl;display:flex;flex-direction:column}
  .video-studio-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:22px;flex-wrap:wrap}
  .video-studio-title{font-size:22px;font-weight:900;color:var(--text-h);letter-spacing:-.3px}
  .video-studio-subtitle{font-size:12px;color:var(--text-soft);margin-top:5px}
  .video-studio-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
  .studio-btn{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 14px;border:1px solid var(--border);border-radius:10px;background:var(--card-bg);color:var(--text-main);font-size:12px;font-weight:800;text-decoration:none;transition:.2s}
  .studio-btn:hover{border-color:var(--primary);color:var(--primary)}
  .studio-btn.primary{background:var(--primary);border-color:var(--primary);color:var(--accent)}
  .studio-btn.primary:hover{filter:brightness(1.08)}
  .studio-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:16px}
  .studio-card{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow-card)}
  .studio-kpi{padding:15px 16px;min-height:122px;position:relative;overflow:hidden}
  .studio-kpi:before{content:"";position:absolute;right:0;top:0;bottom:0;width:3px;background:var(--primary);border-radius:0 14px 14px 0}
  .studio-kpi.success:before{background:var(--success)}.studio-kpi.warning:before{background:var(--warning)}.studio-kpi.danger:before{background:var(--danger)}.studio-kpi.info:before{background:var(--info)}
  .studio-kpi-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:var(--primary-l);color:var(--primary);margin-bottom:11px}
  .studio-kpi.success .studio-kpi-icon{background:var(--success-l);color:var(--success)}.studio-kpi.warning .studio-kpi-icon{background:var(--warning-l);color:var(--warning)}.studio-kpi.danger .studio-kpi-icon{background:var(--danger-l);color:var(--danger)}.studio-kpi.info .studio-kpi-icon{background:var(--info-l);color:var(--info)}
  .studio-kpi-value{font-size:23px;line-height:1;font-weight:900;color:var(--text-h);font-variant-numeric:tabular-nums}
  .studio-kpi-label{font-size:11px;color:var(--text-soft);margin-top:5px}
  .studio-layout{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(320px,.75fr);gap:16px;margin-bottom:16px}
  .studio-panel{padding:18px}
  .studio-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px}
  .studio-panel-title{font-size:13px;font-weight:900;color:var(--text-h);display:flex;align-items:center;gap:7px}.studio-panel-title i{color:var(--primary)}
  .studio-panel-meta{font-size:10px;color:var(--text-soft)}
  .studio-chart{display:flex;align-items:flex-end;gap:8px;height:180px;padding:10px 3px 22px;border-bottom:1px solid var(--divider)}
  .studio-bar{flex:1;min-width:8px;height:100%;display:flex;align-items:flex-end;position:relative}
  .studio-bar-fill{width:100%;min-height:3px;border-radius:6px 6px 2px 2px;background:var(--primary);opacity:.82;transition:height .25s}
  .studio-bar-label{position:absolute;bottom:-19px;right:50%;transform:translateX(50%);font-size:9px;color:var(--text-soft);white-space:nowrap}
  .studio-bar-value{position:absolute;top:-17px;right:50%;transform:translateX(50%);font-size:9px;color:var(--text-soft);opacity:0;transition:opacity .2s}.studio-bar:hover .studio-bar-value{opacity:1}
  .studio-health{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
  .studio-health-item{padding:12px;border:1px solid var(--border);border-radius:11px;background:var(--input-bg)}
  .studio-health-label{font-size:10px;color:var(--text-soft);margin-bottom:7px}.studio-health-value{font-size:17px;font-weight:900;color:var(--text-h)}
  .studio-progress{height:6px;background:var(--border);border-radius:99px;overflow:hidden;margin-top:8px}.studio-progress>span{display:block;height:100%;background:var(--primary);border-radius:inherit}
  .studio-table-wrap{overflow:auto}.studio-table{width:100%;border-collapse:collapse;min-width:650px}.studio-table th{font-size:10px;color:var(--text-soft);font-weight:800;text-align:right;padding:9px 10px;border-bottom:1px solid var(--divider);white-space:nowrap}.studio-table td{font-size:11px;color:var(--text-main);padding:11px 10px;border-bottom:1px solid var(--divider);vertical-align:middle}.studio-table tr:last-child td{border-bottom:0}.studio-table tr:hover td{background:var(--input-bg)}
  .studio-product{font-weight:800;color:var(--text-h)}.studio-muted{font-size:10px;color:var(--text-soft);margin-top:3px}
  .studio-badge{display:inline-flex;align-items:center;gap:4px;border-radius:99px;padding:4px 8px;font-size:10px;font-weight:800;border:1px solid}.studio-badge.success{background:var(--success-l);color:var(--success);border-color:var(--success-m)}.studio-badge.warning{background:var(--warning-l);color:var(--warning);border-color:var(--warning-m)}.studio-badge.danger{background:var(--danger-l);color:var(--danger);border-color:var(--danger-m)}.studio-badge.neutral{background:var(--input-bg);color:var(--text-soft);border-color:var(--border)}
  .studio-empty{padding:28px;text-align:center;color:var(--text-soft);font-size:11px;border:1px dashed var(--border);border-radius:11px;background:var(--input-bg)}
  .studio-source{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--divider)}.studio-source:last-child{border-bottom:0}.studio-source-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:var(--primary-l);color:var(--primary)}.studio-source-name{font-size:11px;font-weight:800;color:var(--text-h);flex:1}.studio-source-status{font-size:10px;color:var(--success);font-weight:800}
  .studio-modal{position:fixed;inset:0;z-index:80;display:none;align-items:center;justify-content:center;padding:20px;background:color-mix(in srgb,var(--text-h) 45%,transparent)}.studio-modal.is-open{display:flex}.studio-modal-card{width:min(720px,100%);max-height:min(78vh,720px);overflow:hidden;background:var(--card-bg);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow-card);display:flex;flex-direction:column}.studio-modal-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid var(--divider)}.studio-modal-title{font-size:14px;font-weight:900;color:var(--text-h)}.studio-modal-close{border:0;background:var(--input-bg);color:var(--text-main);width:32px;height:32px;border-radius:8px;cursor:pointer}.studio-modal-tools{display:grid;grid-template-columns:minmax(0,1fr) 150px;gap:8px;padding:12px 18px;border-bottom:1px solid var(--divider)}.studio-product-list{overflow:auto;padding:10px 18px 16px;display:grid;gap:7px}.studio-product-choice{display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;padding:11px 12px;border:1px solid var(--border);border-radius:10px;background:var(--input-bg);color:var(--text-main);cursor:pointer;text-align:right}.studio-product-choice:hover,.studio-product-choice:focus{border-color:var(--primary);outline:0}.studio-product-choice.is-covered{opacity:.7}.studio-product-choice-main{min-width:0}.studio-product-choice-name{font-size:11px;font-weight:900;color:var(--text-h)}.studio-product-choice-meta{font-size:10px;color:var(--text-soft);margin-top:3px}.studio-product-choice-status{font-size:10px;font-weight:800;color:var(--success);white-space:nowrap}.studio-product-choice-status small{display:block;color:var(--warning);font-size:9px;margin-top:3px}.studio-selected-product{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border:1px solid var(--primary);border-radius:10px;background:var(--primary-l)}.studio-selected-product-actions{display:flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:wrap}.studio-selected-product-name{font-size:12px;font-weight:900;color:var(--text-h)}.studio-manual-box{display:grid;gap:6px;padding:10px;border:1px dashed var(--border);border-radius:10px;background:var(--input-bg)}
  .studio-modal-prompt .studio-modal-card{width:min(860px,100%)}.studio-prompt-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:16px;overflow:auto}.studio-prompt-channel{border:1px solid var(--border);border-radius:12px;padding:12px;background:var(--input-bg)}.studio-prompt-channel h4{font-size:12px;color:var(--text-h);margin:0 0 8px;display:flex;align-items:center;gap:7px}.studio-prompt-channel h4 i{color:var(--primary)}.studio-telegram-buttons{margin:0 16px 16px;padding:12px;border:1px solid var(--border);border-radius:12px;background:var(--input-bg)}.studio-telegram-buttons-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px}.studio-telegram-buttons-title{font-size:12px;font-weight:900;color:var(--text-h)}.studio-telegram-button-list{display:grid;gap:7px}.studio-telegram-button-row{display:grid;grid-template-columns:minmax(120px,.8fr) minmax(180px,1.4fr) 120px 30px;gap:7px;align-items:center}.studio-telegram-button-row .studio-input,.studio-telegram-button-row .studio-select{min-width:0}.studio-telegram-button-remove{width:30px;height:30px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--danger);cursor:pointer}.studio-telegram-button-add{margin-top:8px}.studio-telegram-buttons-help{display:block;font-size:10px;color:var(--text-soft);margin-top:8px}
  .studio-settings{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(300px,.85fr);gap:16px}.studio-form{display:grid;gap:13px}.studio-field{display:grid;gap:6px}.studio-field label{font-size:11px;font-weight:800;color:var(--text-h)}.studio-field small{font-size:10px;color:var(--text-soft)}.studio-input,.studio-select,.studio-textarea{width:100%;border:1px solid var(--border);border-radius:9px;background:var(--input-bg);color:var(--text-main);font:inherit;font-size:11px;padding:10px 11px;outline:0}.studio-input:focus,.studio-select:focus,.studio-textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-l)}.studio-textarea{min-height:82px;resize:vertical}.studio-options{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:7px}.studio-option{position:relative}.studio-option input{position:absolute;opacity:0;pointer-events:none}.studio-option label{display:flex;flex-direction:column;gap:5px;align-items:center;justify-content:center;min-height:64px;border:1px solid var(--border);border-radius:10px;background:var(--input-bg);padding:8px;text-align:center;cursor:pointer;font-size:10px;color:var(--text-soft);transition:.2s}.studio-option label i{font-size:15px;color:var(--primary)}.studio-option input:checked+label{border-color:var(--primary);background:var(--primary-l);color:var(--text-h);box-shadow:inset 0 0 0 1px var(--primary)}.studio-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.studio-check{display:flex;align-items:center;gap:8px;padding:9px 10px;border:1px solid var(--border);border-radius:9px;background:var(--input-bg);font-size:10px;color:var(--text-main);cursor:pointer}.studio-check input{accent-color:var(--primary);width:15px;height:15px}.studio-alert{padding:10px 12px;border-radius:9px;background:var(--success-l);color:var(--success);font-size:11px;font-weight:800;margin-bottom:15px}.studio-error{padding:8px 10px;border-radius:8px;background:var(--danger-l);color:var(--danger);font-size:10px}.studio-conditional.is-hidden{display:none}.studio-hook-list{display:grid;gap:8px;max-height:390px;overflow:auto}.studio-hook{padding:11px;border:1px solid var(--border);border-radius:10px;background:var(--input-bg)}.studio-hook-top{display:flex;align-items:center;justify-content:space-between;gap:8px}.studio-hook-title{font-size:11px;font-weight:900;color:var(--text-h)}.studio-hook-text{font-size:11px;color:var(--text-main);line-height:1.8;margin-top:6px}.studio-hook-tags{font-size:9px;color:var(--text-soft);margin-top:5px}.studio-link-btn{border:0;background:transparent;color:var(--danger);cursor:pointer;font-size:10px;padding:3px}
  .studio-conditional.is-hidden{display:none}.studio-images{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px}.studio-image-choice{position:relative}.studio-image-choice input{position:absolute;opacity:0}.studio-image-choice label{display:block;aspect-ratio:1;border:2px solid var(--border);border-radius:9px;overflow:hidden;cursor:pointer;background:var(--input-bg);transition:.2s}.studio-image-choice img{width:100%;height:100%;object-fit:cover}.studio-image-choice input:checked+label{border-color:var(--primary);box-shadow:0 0 0 2px var(--primary-l)}.studio-image-choice input:checked+label:after{content:'✓';position:absolute;top:5px;right:5px;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--primary);color:var(--accent);font-size:12px;font-weight:900}.studio-queue{display:grid;gap:8px}.studio-job{display:grid;grid-template-columns:minmax(0,1fr) auto auto auto;align-items:center;gap:12px;padding:11px;border:1px solid var(--border);border-radius:10px;background:var(--input-bg)}.studio-job-main{min-width:0}.studio-job-title{font-size:11px;font-weight:900;color:var(--text-h);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.studio-job-meta{font-size:10px;color:var(--text-soft);margin-top:4px}.studio-job-status{font-size:10px;font-weight:800}.studio-job-status.queued{color:var(--warning)}.studio-job-status.processing{color:var(--info)}.studio-job-status.completed{color:var(--success)}.studio-job-status.failed{color:var(--danger)}.studio-job-actions{display:flex;align-items:center;gap:4px}.studio-job-edit-toggle{border:1px solid var(--border);background:var(--card-bg);color:var(--text-soft);border-radius:8px;padding:5px 8px;font-size:10px;cursor:pointer}.studio-job-edit-toggle:hover{border-color:var(--primary);color:var(--primary)}.studio-job-editor{grid-column:1/-1;display:grid;gap:8px;padding:10px;border-top:1px solid var(--divider);margin-top:2px}.studio-job-editor.is-hidden{display:none}.studio-job-editor textarea{min-height:66px}.studio-job-editor small{font-size:10px;color:var(--text-soft)}
  @media(max-width:1100px){.studio-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.studio-layout,.studio-settings{grid-template-columns:1fr}}
  @media(max-width:650px){.video-studio-page{padding:15px}.studio-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.studio-kpi{min-height:108px;padding:12px}.studio-kpi-value{font-size:20px}.studio-health{grid-template-columns:1fr}.studio-prompt-grid{grid-template-columns:1fr}.video-studio-title{font-size:19px}}
  .studio-preview-head{display:flex;align-items:center;justify-content:space-between;gap:10px}.studio-preview{display:grid;gap:12px;margin-top:9px;padding:12px;border:1px solid var(--border);border-radius:11px;background:var(--input-bg)}.studio-preview-block{display:grid;gap:7px}.studio-preview-label{font-size:10px;font-weight:900;color:var(--text-h)}.studio-preview-tabs{display:flex;gap:6px;flex-wrap:wrap}.studio-preview-tab{border:1px solid var(--border);background:var(--card-bg);color:var(--text-main);border-radius:8px;padding:6px 9px;font-size:10px;cursor:pointer;text-align:right}.studio-preview-tab.is-selected{border-color:var(--primary);background:var(--primary-l);color:var(--text-h);box-shadow:inset 0 0 0 1px var(--primary)}.studio-preview-tab i{color:var(--success);margin-left:4px}.studio-preview-status{font-size:10px;color:var(--text-soft)}
  @font-face{font-family:'B_Yekan';src:url('{{ asset('fonts/B_Yekan.ttf') }}') format('truetype');font-display:swap}
  @font-face{font-family:'Modam';src:url('{{ asset('fonts/video/Modam-Medium.ttf') }}') format('truetype');font-display:swap}
  @font-face{font-family:'Peyda';src:url('{{ asset('fonts/video/Peyda-Medium.ttf') }}') format('truetype');font-display:swap}
  @font-face{font-family:'Doran';src:url('{{ asset('fonts/video/Doran-Regular.ttf') }}') format('truetype');font-display:swap}
  @font-face{font-family:'Abar';src:url('{{ asset('fonts/video/AbarMid-Regular.ttf') }}') format('truetype');font-display:swap}
  @font-face{font-family:'IRANSansX';src:url('{{ asset('fonts/IRANSansXFaNum-RegularD4.ttf') }}') format('truetype');font-display:swap}
  @font-face{font-family:'YekanBakh';src:url('{{ asset('fonts/video/YekanBakh-Medium.ttf') }}') format('truetype');font-display:swap}
  .studio-font-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.studio-font-option{position:relative}.studio-font-option input{position:absolute;opacity:0;pointer-events:none}.studio-font-option label{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;min-height:62px;padding:8px;border:1px solid var(--border);border-radius:10px;background:var(--input-bg);cursor:pointer;color:var(--text-main);text-align:center;transition:.2s}.studio-font-option label small{font-family:inherit;font-size:9px;color:var(--text-soft)}.studio-font-option input:checked+label{border-color:var(--primary);background:var(--primary-l);box-shadow:inset 0 0 0 1px var(--primary)}
  .studio-preview-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.studio-preview-option{position:relative;border:1px solid var(--border);border-radius:10px;padding:8px;background:var(--card-bg);cursor:pointer}.studio-preview-option.is-selected{border-color:var(--primary);background:var(--primary-l);box-shadow:inset 0 0 0 1px var(--primary)}.studio-preview-option-check{display:flex;align-items:center;gap:5px;font-size:10px;font-weight:800;color:var(--text-soft);margin-bottom:5px}.studio-preview-option.is-selected .studio-preview-option-check{color:var(--success)}.studio-preview-option textarea{min-height:100px;padding:7px;font-size:10px;line-height:1.8}.studio-preview-option textarea[readonly]{cursor:pointer}.studio-preview-option textarea:not([readonly]){cursor:text}
  .studio-telegram-live{margin-bottom:16px}.studio-telegram-preview-grid{display:grid;grid-template-columns:minmax(240px,.65fr) minmax(0,1.35fr);gap:16px;align-items:center}.studio-phone{width:min(310px,100%);margin:auto;padding:10px;border:7px solid var(--text-h);border-radius:30px;background:var(--input-bg);box-shadow:var(--shadow-card)}.studio-phone-notch{width:90px;height:18px;margin:-2px auto 8px;border-radius:0 0 12px 12px;background:var(--text-h)}.studio-phone-head{display:flex;align-items:center;gap:8px;padding:8px 5px;border-bottom:1px solid var(--divider);font-size:11px;font-weight:900;color:var(--text-h)}.studio-phone-avatar{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--primary);color:var(--accent)}.studio-phone-chat{padding:12px 4px;min-height:300px;background:var(--page-bg)}.studio-phone-post{max-width:94%;margin-right:auto;padding:8px;border-radius:10px 10px 2px 10px;background:var(--card-bg);box-shadow:var(--shadow-card)}.studio-phone-media{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px}.studio-phone-media img{width:100%;aspect-ratio:1;object-fit:cover;border-radius:7px}.studio-phone-caption{white-space:pre-wrap;font-size:10px;line-height:1.8;color:var(--text-main);margin-top:7px}.studio-phone-buttons{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px;margin-top:8px}.studio-phone-button{display:flex;align-items:center;justify-content:center;min-height:27px;padding:4px 6px;border-radius:6px;font-size:9px;font-weight:800;color:var(--accent);background:var(--primary);text-decoration:none;text-align:center}.studio-phone-button.success{background:var(--success)}.studio-phone-button.danger{background:var(--danger)}.studio-phone-button.full{grid-column:1/-1}.studio-telegram-preview-note{font-size:11px;color:var(--text-soft);line-height:2}.studio-telegram-preview-note strong{color:var(--text-h)}
  .studio-smart-fields{display:grid;gap:12px}.studio-smart-field{display:grid;gap:7px;padding:12px;border:1px solid var(--border);border-radius:12px;background:var(--input-bg)}.studio-smart-field>small{font-size:10px;color:var(--text-soft)}.studio-smart-toggle{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:0!important;border:0!important;background:transparent!important;color:var(--text-h)!important;font-size:12px!important;font-weight:900!important;cursor:pointer}.studio-smart-toggle span{display:flex;align-items:center;gap:7px}.studio-smart-toggle span i{color:var(--primary)}.studio-smart-toggle input[type=checkbox]{accent-color:var(--primary);width:17px;height:17px}.studio-smart-toggle-main{display:flex;align-items:center;gap:8px}.studio-regenerate{border:1px solid var(--border);background:var(--card-bg);color:var(--primary);border-radius:8px;padding:5px 8px;font-size:10px;font-weight:800;cursor:pointer;white-space:nowrap}.studio-regenerate:hover{border-color:var(--primary);background:var(--primary-l)}.studio-regenerate:disabled{opacity:.6;cursor:wait}.studio-smart-field .studio-preview-options{margin-top:2px;min-height:112px}.studio-preview-option.is-loading{opacity:.8;cursor:wait}.studio-preview-option.is-loading .studio-preview-option-check{color:var(--primary)}.studio-preview-option.is-loading textarea{background:var(--input-bg);color:var(--text-soft)}.studio-preview-option:not(.is-selected) .studio-preview-option-check i{visibility:hidden}.studio-font-grid{grid-template-columns:repeat(6,minmax(0,1fr));gap:6px}.studio-font-option label{min-height:50px;padding:6px;font-size:10px}.studio-font-option label small{font-size:8px}.studio-telegram-caption-smart{display:grid;gap:8px;margin-top:12px;padding:12px;border:1px solid var(--border);border-radius:12px;background:var(--input-bg)}.studio-telegram-caption-smart-head{display:flex;align-items:center;justify-content:space-between;gap:8px}.studio-telegram-caption-smart-head label{display:flex;align-items:center;gap:7px;font-size:11px;font-weight:900;color:var(--text-h)}.studio-telegram-caption-smart-head input{accent-color:var(--primary);width:16px;height:16px}.studio-telegram-caption-editor{display:grid;gap:6px;margin-top:12px}.studio-telegram-caption-editor label{font-size:11px;font-weight:900;color:var(--text-h)}.studio-telegram-caption-editor textarea{min-height:110px}.studio-telegram-live-actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:10px}.studio-telegram-preview-note{display:block;max-width:720px;margin:0 auto}.studio-phone{width:min(372px,100%);max-width:100%;overflow:hidden;box-sizing:border-box}.studio-phone-chat{min-height:720px;overflow:hidden}.studio-phone-post{min-height:660px;max-width:100%;overflow:hidden;box-sizing:border-box;word-break:break-word}.studio-phone-media{max-width:100%;overflow:hidden}.studio-phone-media img{max-width:100%;height:auto;display:block}.studio-phone-caption{font-size:11px;line-height:1.85;overflow-wrap:anywhere}.studio-phone-button{min-height:36px;border:1px solid var(--divider);border-radius:7px;background:var(--info-l);color:var(--info);font-size:11px;font-weight:700;box-shadow:none}.studio-phone-button.success{background:var(--success-l);color:var(--success)}.studio-phone-button.danger{background:var(--danger-l);color:var(--danger)}.studio-phone-comments{display:flex;align-items:center;justify-content:center;gap:5px;border-top:1px solid var(--divider);margin-top:8px;padding-top:8px;color:var(--info);font-size:10px;font-weight:700}.studio-telegram-caption-smart [data-telegram-caption-options]{grid-template-columns:repeat(2,minmax(0,1fr))}.studio-telegram-preview-grid{grid-template-columns:1fr;align-items:start}
  #open-prompt-mother{display:none}.studio-smart-fields{display:grid;gap:12px}.studio-smart-field{display:grid;gap:7px;padding:12px;border:1px solid var(--border);border-radius:12px;background:var(--input-bg)}.studio-smart-field>small{font-size:10px;color:var(--text-soft)}.studio-smart-toggle{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:0!important;border:0!important;background:transparent!important;color:var(--text-h)!important;font-size:12px!important;font-weight:900!important;cursor:pointer}.studio-smart-toggle span{display:flex;align-items:center;gap:7px}.studio-smart-toggle span i{color:var(--primary)}.studio-smart-toggle input[type=checkbox]{accent-color:var(--primary);width:17px;height:17px}.studio-smart-toggle-main{display:flex;align-items:center;gap:8px}.studio-regenerate{border:1px solid var(--border);background:var(--card-bg);color:var(--primary);border-radius:8px;padding:5px 8px;font-size:10px;font-weight:800;cursor:pointer;white-space:nowrap}.studio-regenerate:hover{border-color:var(--primary);background:var(--primary-l)}.studio-regenerate:disabled{opacity:.6;cursor:wait}.studio-smart-field .studio-preview-options{margin-top:2px;min-height:112px}.studio-preview-option.is-loading{opacity:.8;cursor:wait}.studio-preview-option.is-loading .studio-preview-option-check{color:var(--primary)}.studio-preview-option.is-loading textarea{background:var(--input-bg);color:var(--text-soft)}.studio-preview-option:not(.is-selected) .studio-preview-option-check i{visibility:hidden}.studio-font-grid{grid-template-columns:repeat(6,minmax(0,1fr));gap:6px}.studio-font-option label{min-height:50px;padding:6px;font-size:10px}.studio-font-option label small{font-size:8px}.studio-telegram-caption-smart{display:grid;gap:8px;margin-top:12px;padding:12px;border:1px solid var(--border);border-radius:12px;background:var(--input-bg)}.studio-telegram-caption-smart-head{display:flex;align-items:center;justify-content:space-between;gap:8px}.studio-telegram-caption-smart-head label{display:flex;align-items:center;gap:7px;font-size:11px;font-weight:900;color:var(--text-h)}.studio-telegram-caption-smart-head input{accent-color:var(--primary);width:16px;height:16px}.studio-telegram-caption-editor{display:grid;gap:6px;margin-top:12px}.studio-telegram-caption-editor label{font-size:11px;font-weight:900;color:var(--text-h)}.studio-telegram-caption-editor textarea{min-height:110px}.studio-telegram-live-actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:10px}.studio-telegram-preview-note{display:block;max-width:720px;margin:0 auto}.studio-phone{width:min(372px,100%)}.studio-phone-chat{min-height:360px}.studio-phone-post{min-height:330px}.studio-telegram-preview-grid{grid-template-columns:1fr;align-items:start}
  @media(max-width:1100px){.studio-font-grid{grid-template-columns:repeat(4,minmax(0,1fr))}}
  @media(max-width:650px){.studio-font-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
  .studio-phone-chat{min-height:720px}.studio-phone-post{min-height:660px}
  /* Final layout pass: compact source/ratio controls and keep Telegram preview inside its card. */
  .studio-telegram-caption-editor{display:none!important}
  .studio-telegram-preview-note{width:100%;max-width:760px;box-sizing:border-box;margin-inline:auto;overflow:hidden}
  .studio-telegram-caption-smart,.studio-telegram-buttons{width:min(100%,760px);max-width:100%;box-sizing:border-box;margin-inline:auto}
  .studio-telegram-button-row{grid-template-columns:minmax(0,1.05fr) minmax(0,1.6fr) minmax(90px,.8fr) 30px;min-width:0}
  .studio-telegram-button-row>*{min-width:0;max-width:100%;box-sizing:border-box}
  #source-options{grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
  #source-options .studio-option label{min-height:50px;padding:6px;font-size:10px}
  #aspect-ratio-field .studio-options{grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
  #aspect-ratio-field .studio-option label{min-height:0;aspect-ratio:1/1;padding:4px;font-size:9px}
  #aspect-ratio-field .studio-option label i{display:none}
  #aspect-ratio-field .studio-option label span{font-size:9px}
  @media(min-width:1000px){
    #studio-settings-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}
    #studio-settings-form>*{grid-column:1/-1}
    #studio-settings-form>#source-mode-field{grid-column:1;grid-row:4}
    #studio-settings-form>#source-url-field{grid-column:1/-1;grid-row:5}
    #studio-settings-form>#aspect-ratio-field{grid-column:2;grid-row:4}
  }
  @media(max-width:999px){#studio-settings-form{display:grid;grid-template-columns:1fr;gap:13px}#studio-settings-form>*{grid-column:1!important;grid-row:auto!important}}
  @media(max-width:760px){.studio-telegram-button-row{grid-template-columns:1fr 1fr}.studio-telegram-button-remove{grid-column:1/-1;width:100%}}
  /* Telegram message bubble grows with its content; the channel background continues below it. */
  #source-options{width:75%;max-width:240px}
  #aspect-ratio-field .studio-options{width:50%;max-width:150px}
  #aspect-ratio-field .studio-option label{width:100%;box-sizing:border-box}
  .studio-phone-chat{min-height:720px;background-color:var(--primary-l);background-image:radial-gradient(circle at 18% 20%,color-mix(in srgb,var(--primary) 10%,transparent) 0 2px,transparent 3px),radial-gradient(circle at 72% 65%,color-mix(in srgb,var(--primary) 8%,transparent) 0 1px,transparent 3px);background-size:28px 28px,38px 38px}
  .studio-phone-post{height:auto;min-height:0;overflow:visible}
  .studio-phone-buttons{width:94%;max-width:94%;margin:8px auto 0;box-sizing:border-box}
  .studio-phone-buttons:empty{display:none}
  @media(max-width:999px){#source-options,#aspect-ratio-field .studio-options{width:100%;max-width:none}}
  .studio-media-controls{grid-column:1/-1;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:12px;padding:14px;border:1px solid var(--border);border-radius:14px;background:var(--input-bg);box-sizing:border-box}
  .studio-media-controls>#source-url-field{grid-column:1/-1}
  .studio-media-controls>.studio-field{min-width:0}
  @media(max-width:999px){.studio-media-controls{grid-template-columns:1fr}.studio-media-controls>#source-url-field{grid-column:1}}
  .studio-settings{align-items:stretch}
  .studio-settings>.studio-card{height:100%;box-sizing:border-box;margin-bottom:0!important}
  .studio-library-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px;align-items:stretch}
  .studio-library-grid>.studio-card{min-width:0;height:100%;box-sizing:border-box;margin-bottom:0!important}
  .video-studio-page>section,.video-studio-page>.studio-settings,.video-studio-page>.studio-library-grid,.video-studio-page>.studio-layout{margin-bottom:16px!important}
  .video-studio-page>.studio-library-grid+*{margin-top:0}
  @media(max-width:999px){.studio-library-grid{grid-template-columns:1fr}}
  .video-studio-head{order:1}.studio-grid{order:2}.studio-settings{order:3}#studio-system-layout{order:4}#studio-queue-panel{order:5}#studio-produced-panel{order:6}#studio-latest-videos-panel{order:7}#studio-latest-tests-panel{order:8}.studio-library-grid{order:9}.studio-modal{order:99}
  .studio-telegram-live .studio-phone-chat{min-height:648px}
  .studio-telegram-caption-smart{margin-top:0;margin-bottom:16px;padding:16px}.studio-telegram-caption-smart .studio-preview-option textarea{min-height:126px}.studio-telegram-buttons{margin-top:0;margin-bottom:4px;padding:16px}.studio-telegram-buttons .studio-telegram-button-row{min-height:42px}
  #aspect-ratio-field .studio-options{grid-template-columns:repeat(4,minmax(0,1fr));width:100%;max-width:none}
  #aspect-ratio-field .studio-option label{max-width:62px;justify-self:center}
</style>
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="video-studio-page" id="content">
    @if(session('success'))<div class="studio-alert"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
    @if(isset($errors) && $errors->any())<div class="studio-error">اطلاعات کامل نیست؛ {{ $errors->first() }}</div>@endif
    <div class="video-studio-head">
      <div>
        <div class="video-studio-title">تولید خودکار ویدیو</div>
        <div class="video-studio-subtitle">مدیریت محصولات، خروجی‌ها، خطاها و وضعیت پایپ‌لاین در یک نمای واحد</div>
      </div>
      <div class="video-studio-actions">
        <a class="studio-btn" href="{{ route('admin.products') }}"><i class="fa-solid fa-box-open"></i> محصولات</a>
        <a class="studio-btn" href="https://docs.google.com/spreadsheets/d/1r44fnFeUL6ndq_XmVP0XNW6J16Kekz_6pBEnL_WHDa4/edit" target="_blank" rel="noopener"><i class="fa-solid fa-table-list"></i> گزارش شیت</a>
        <button class="studio-btn" type="button" id="open-prompt-mother-top"><i class="fa-solid fa-wand-magic-sparkles"></i> تنظیم پرامپت اینستا و تلگرام</button>
        <a class="studio-btn primary" href="#studio-settings"><i class="fa-solid fa-clapperboard"></i> ساخت ویدیو</a>
      </div>
    </div>

    <div class="studio-grid">
      <div class="studio-card studio-kpi"><div class="studio-kpi-icon"><i class="fa-solid fa-clapperboard"></i></div><div class="studio-kpi-value">{{ number_format($videoCount) }}</div><div class="studio-kpi-label">کل خروجی‌های ویدیو</div></div>
      <div class="studio-card studio-kpi success"><div class="studio-kpi-icon"><i class="fa-solid fa-circle-check"></i></div><div class="studio-kpi-value">{{ number_format($completedCount) }}</div><div class="studio-kpi-label">تولیدهای موفق</div></div>
      <div class="studio-card studio-kpi warning"><div class="studio-kpi-icon"><i class="fa-solid fa-spinner"></i></div><div class="studio-kpi-value">{{ number_format($processingCount) }}</div><div class="studio-kpi-label">در صف پردازش</div></div>
      <div class="studio-card studio-kpi danger"><div class="studio-kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="studio-kpi-value">{{ number_format($failedCount) }}</div><div class="studio-kpi-label">خروجی ناموفق</div></div>
      <div class="studio-card studio-kpi info"><div class="studio-kpi-icon"><i class="fa-solid fa-boxes-stacked"></i></div><div class="studio-kpi-value">{{ number_format($coveredProducts) }} / {{ number_format($activeProducts) }}</div><div class="studio-kpi-label">محصول دارای خروجی / فعال</div></div>
    </div>

    <div class="studio-settings" style="margin-bottom:16px">
      <section class="studio-card studio-panel" id="studio-settings">
        <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-sliders"></i> تنظیمات ساخت</div><div class="studio-panel-meta">قابل تغییر برای هر محصول</div></div>
        <form id="studio-settings-form" class="studio-form" data-product-id="{{ $selectedProduct?->id ?? (int) request()->query('product_id') }}" method="POST" action="{{ route('admin.video-studio.settings.update') }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="_method" id="studio-form-method" value="PATCH">
          <div class="studio-field"><label>محصول هدف</label><input type="hidden" id="studio-product" name="product_id" value="{{ $selectedProduct?->id ?? (int) request()->query('product_id') }}"><div class="studio-selected-product"><div><div class="studio-selected-product-name">{{ $selectedProduct?->name_fa ?? 'تنظیمات پیش‌فرض همه محصولات' }}</div>@if($selectedProduct)<div class="studio-product-count"><i class="fa-solid fa-clapperboard"></i> {{ (int) ($completedVideoCounts[(int) $selectedProduct->id] ?? 0) }} ویدیو ساخته‌شده @if((int) ($pendingVideoCounts[(int) $selectedProduct->id] ?? 0) > 0)<span class="studio-product-count pending">+ {{ (int) ($pendingVideoCounts[(int) $selectedProduct->id] ?? 0) }} در صف</span>@endif</div>@endif</div><div class="studio-selected-product-actions"><button class="studio-btn" type="button" id="open-product-picker"><i class="fa-solid fa-magnifying-glass"></i> انتخاب محصول</button><button class="studio-btn" type="button" id="random-product-picker" title="اولویت با محصولاتی است که هنوز ویدیویی برایشان ساخته نشده"><i class="fa-solid fa-shuffle"></i> انتخاب تصادفی</button></div></div><small>با انتخاب محصول از پنجره‌ی جست‌وجو، تصاویر همان محصول پایین همین بخش نمایش داده می‌شود.</small></div>
          @if($selectedProduct)
            <div class="studio-field"><label>تصاویر محصول برای ویدیو</label><div class="studio-images">@forelse($productImages as $image)<div class="studio-image-choice"><input id="studio-image-{{ $loop->index }}" type="checkbox" name="selected_images[]" value="{{ $image['url'] }}" checked><label for="studio-image-{{ $loop->index }}"><img src="{{ $image['url'] }}" alt="تصویر {{ $loop->iteration }}"></label></div>@empty<div class="studio-empty" style="grid-column:1/-1">برای این محصول تصویر قابل استفاده پیدا نشد.</div>@endforelse</div><small>تصویرهای انتخاب‌شده همراه سفارش ساخت به ورکفلو ارسال می‌شوند.</small></div>
          @endif
          <div class="studio-field"><label>فونت نوشته‌های ویدیو</label><div class="studio-font-grid" id="font-family">@foreach($fonts as $font)<div class="studio-font-option"><input id="font-{{ $font->slug }}" type="radio" name="font_family" value="{{ $font->slug }}" @checked(($settings->font_family ?? 'B_Yekan') === $font->slug)><label for="font-{{ $font->slug }}" style="font-family:'{{ $font->slug }}'"><span>{{ $font->name }}</span><small>نمونه متن فارسی</small></label></div>@endforeach</div><small>یکان حالت پیش‌فرض است و برای هر سفارش قابل تغییر است.</small></div>
          <input type="hidden" name="build_now" id="build-now" value="0"><input type="hidden" name="preview_hook" id="preview-hook"><input type="hidden" name="preview_caption" id="preview-caption"><input type="hidden" name="preview_keyword" id="preview-keyword"><input type="hidden" name="telegram_caption_text" id="telegram-caption-hidden" value="{{ old('telegram_caption_text', $settings->telegram_caption_text ?? '') }}">
          <div class="studio-media-controls" id="studio-media-controls"><div class="studio-field" id="source-mode-field"><label>منبع صدا</label><div class="studio-options" id="source-options">
            @foreach(['auto'=>['fa-wand-magic-sparkles','خودکار'],'upload'=>['fa-file-audio','فایل مستقیم'],'music'=>['fa-music','فایل موزیک'],'video'=>['fa-film','ویدیوی منبع']] as $mode=>$option)
              <div class="studio-option"><input id="source-{{ $mode }}" type="radio" name="source_mode" value="{{ $mode }}" @checked(($settings->source_mode ?? 'auto') === $mode)><label for="source-{{ $mode }}"><i class="fa-solid {{ $option[0] }}"></i>{{ $option[1] }}</label></div>
            @endforeach
          </div><small id="source-help">منبع انتخابی بعد از اتصال به ورکفلو، هنگام ساخت استفاده می‌شود.</small></div>
          <div class="studio-field" id="source-url-field"><label for="source-url">منبع صدا</label><select class="studio-select" id="source-library" name="source_library_id"><option value="">بدون انتخاب از کتابخانه</option>@foreach($sources as $source)<option value="{{ $source->id }}" data-source-type="{{ $source->type }}">{{ $source->name }} · {{ $source->type === 'video' ? 'ویدیوی منبع' : 'موزیک' }} · {{ $source->used_count }} استفاده</option>@endforeach</select><input id="source-url" class="studio-input" type="url" name="source_url" value="{{ old('source_url', $settings->source_url) }}" placeholder="لینک فایل موزیک یا ویدیوی منبع"><input class="studio-input" type="file" name="source_file" accept="audio/*,video/mp4,video/quicktime,video/webm"><small id="source-url-help">می‌توانی یک منبع از کتابخانه انتخاب کنی یا لینک/فایل تازه بدهی.</small></div>
          <div class="studio-field" id="aspect-ratio-field"><label>قاب خروجی</label><div class="studio-options">
            @foreach(['9:16'=>['fa-mobile-screen-button','استوری عمودی'],'1:1'=>['fa-square','مربع'],'4:5'=>['fa-image','پست عمودی'],'16:9'=>['fa-display','افقی']] as $ratio=>$option)
              <div class="studio-option"><input id="ratio-{{ str_replace(':','-',$ratio) }}" type="radio" name="aspect_ratio" value="{{ $ratio }}" @checked(($settings->aspect_ratio ?? '9:16') === $ratio)><label for="ratio-{{ str_replace(':','-',$ratio) }}"><i class="fa-solid {{ $option[0] }}"></i>{{ $option[1] }}<span dir="ltr">{{ $ratio }}</span></label></div>
            @endforeach
          </div><small>حالت پیش‌فرض استوری است و برای هر خروجی قابل تغییر است.</small></div></div>
          <div class="studio-smart-fields" aria-label="کنترل‌های هوشمند و پیشنهادهای متن">
            <div class="studio-smart-field">
              <label class="studio-smart-toggle"><span class="studio-smart-toggle-main"><span><i class="fa-solid fa-bolt"></i> ساخت هوک با هوش مصنوعی</span><button class="studio-regenerate" type="button" data-regenerate-preview="hook">ساخت مجدد</button></span><input type="hidden" name="auto_generate_hook" value="0"><input type="checkbox" name="auto_generate_hook" value="1" @checked($settings->auto_generate_hook)></label>
              <small>سه پیشنهاد هم‌زمان زیر همین گزینه نمایش داده می‌شود؛ اولی به‌صورت پیش‌فرض انتخاب است.</small>
              <div class="studio-preview-options" data-preview-tabs="hook"></div>
              <div class="studio-field studio-conditional" id="hook-manual"><label for="hook-text-manual">متن دستی هوک</label><div class="studio-manual-box"><input id="hook-text-manual" class="studio-input" name="hook_text" value="{{ old('hook_text', $settings->hook_text ?? '') }}" placeholder="با خاموش‌کردن هوش مصنوعی، متن را اینجا ویرایش کن."><small>با روشن‌بودن هوش مصنوعی، متن‌های پیشنهادی فقط خواندنی هستند.</small></div></div>
            </div>
            <div class="studio-smart-field">
              <label class="studio-smart-toggle"><span class="studio-smart-toggle-main"><span><i class="fa-solid fa-pen-nib"></i> ساخت کپشن با هوش مصنوعی</span><button class="studio-regenerate" type="button" data-regenerate-preview="caption">ساخت مجدد</button></span><input type="hidden" name="auto_generate_caption" value="0"><input type="checkbox" name="auto_generate_caption" value="1" @checked($settings->auto_generate_caption)></label>
              <small>هر سه نسخه را ببین، یکی را انتخاب کن و در صورت خاموش‌کردن هوش مصنوعی ویرایشش کن.</small>
              <div class="studio-preview-options" data-preview-tabs="caption"></div>
              <div class="studio-field studio-conditional" id="caption-manual"><label for="caption-text-manual">متن دستی کپشن</label><div class="studio-manual-box"><textarea id="caption-text-manual" class="studio-textarea" name="caption_text" placeholder="با خاموش‌کردن هوش مصنوعی، کپشن را اینجا ویرایش کن.">{{ old('caption_text', $settings->caption_text ?? '') }}</textarea><small>متن انتخاب‌شده هنگام ساخت سفارش ارسال می‌شود.</small></div></div>
            </div>
            <div class="studio-smart-field">
              <label class="studio-smart-toggle"><span class="studio-smart-toggle-main"><span><i class="fa-solid fa-key"></i> ساخت کلمهٔ کلیدی و پاسخ دایرکت</span><button class="studio-regenerate" type="button" data-regenerate-preview="keyword">ساخت مجدد</button></span><input type="hidden" name="auto_generate_keyword" value="0"><input id="auto-keyword-toggle" type="checkbox" name="auto_generate_keyword" value="1" @checked($settings->auto_generate_keyword)></label>
              <small>کلمهٔ کلیدی مناسب محصول و پاسخ دایرکت در سه پیشنهاد تولید می‌شود.</small>
              <div class="studio-preview-options" data-preview-tabs="keyword"></div>
              <div class="studio-field studio-conditional" id="keyword-settings"><label for="keyword">کلمهٔ کلیدی و متن پاسخ دایرکت دستی</label><div class="studio-manual-box"><input id="keyword" class="studio-input" name="keyword" value="{{ old('keyword', $settings->keyword) }}" placeholder="مثلاً: قیمت"><textarea class="studio-textarea" name="dm_template" placeholder="متن آماده پاسخ به کامنت یا دایرکت...">{{ old('dm_template', $settings->dm_template) }}</textarea><small>با خاموش‌کردن هوش مصنوعی، این دو مقدار دستی قابل ویرایش هستند.</small></div></div>
            </div>
          </div>
          <div class="studio-field"><label>پروفایل‌های پرامپت مادر</label><input type="hidden" id="prompt-profile-fallback" name="prompt_profile" value="{{ old('prompt_profile', $settings->prompt_profile) }}"><button class="studio-btn" type="button" id="open-prompt-mother"><i class="fa-solid fa-wand-magic-sparkles"></i> تنظیم پرامپت اینستاگرام و تلگرام</button><input class="studio-input" type="file" name="prompt_file" accept=".txt,.md,text/plain,text/markdown"><small>پروفایل اینستاگرام برای ساخت فعلی استفاده می‌شود؛ پروفایل تلگرام برای مرحلهٔ انتشار کانال آماده و ذخیره می‌شود.</small><div class="studio-modal studio-modal-prompt" id="prompt-mother-modal" role="dialog" aria-modal="true" aria-labelledby="prompt-mother-title"><div class="studio-modal-card"><div class="studio-modal-head"><div class="studio-modal-title" id="prompt-mother-title">پرامپت‌های مادر تولید محتوا</div><button class="studio-modal-close" type="button" id="close-prompt-mother" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button></div><div class="studio-prompt-grid"><div class="studio-prompt-channel"><h4><i class="fa-brands fa-instagram"></i> پرامپت اینستاگرام</h4><textarea class="studio-textarea" id="instagram-prompt" name="instagram_prompt" rows="14" placeholder="قواعد هوک، کپشن، کلمهٔ کلیدی و دایرکت اینستاگرام...">{{ old('instagram_prompt', $settings->instagram_prompt ?: $settings->prompt_profile) }}</textarea></div><div class="studio-prompt-channel"><h4><i class="fa-brands fa-telegram"></i> پرامپت تلگرام</h4><textarea class="studio-textarea" name="telegram_prompt" rows="14" placeholder="قواعد عنوان و کپشن اختصاصی کانال تلگرام...">{{ old('telegram_prompt', $settings->telegram_prompt) }}</textarea></div></div><div class="studio-telegram-buttons"><div class="studio-telegram-buttons-head"><div class="studio-telegram-buttons-title"><i class="fa-solid fa-link"></i> دکمه‌های لینک‌دار تلگرام</div><label class="studio-check"><input type="hidden" name="telegram_buttons_enabled" value="0"><input type="checkbox" name="telegram_buttons_enabled" value="1" @checked(is_array($settings->telegram_buttons ?? null) && count($settings->telegram_buttons) > 0)> فعال‌سازی برای خروجی تلگرام</label></div><div class="studio-telegram-button-list" id="telegram-button-list">@forelse((is_array($settings->telegram_buttons ?? null) ? $settings->telegram_buttons : []) as $telegramButton)<div class="studio-telegram-button-row" data-telegram-button-row><input class="studio-input" name="telegram_button_label[]" value="{{ $telegramButton['label'] ?? '' }}" placeholder="متن دکمه"><input class="studio-input" type="url" name="telegram_button_url[]" value="{{ $telegramButton['url'] ?? '' }}" placeholder="لینک مقصد"><select class="studio-select" name="telegram_button_style[]"><option value="primary" @selected(($telegramButton['style'] ?? 'primary') === 'primary')>آبی اصلی</option><option value="success" @selected(($telegramButton['style'] ?? '') === 'success')>سبز موفق</option><option value="danger" @selected(($telegramButton['style'] ?? '') === 'danger')>قرمز هشدار</option></select><button class="studio-telegram-button-remove" type="button" data-remove-telegram-button aria-label="حذف دکمه"><i class="fa-solid fa-trash"></i></button></div>@empty<div class="studio-telegram-button-row" data-telegram-button-row><input class="studio-input" name="telegram_button_label[]" placeholder="متن دکمه، مثلاً مشاهده محصول"><input class="studio-input" type="url" name="telegram_button_url[]" placeholder="https://..."><select class="studio-select" name="telegram_button_style[]"><option value="primary">آبی اصلی</option><option value="success">سبز موفق</option><option value="danger">قرمز هشدار</option></select><button class="studio-telegram-button-remove" type="button" data-remove-telegram-button aria-label="حذف دکمه"><i class="fa-solid fa-trash"></i></button></div>@endforelse</div><button class="studio-btn studio-telegram-button-add" type="button" id="add-telegram-button"><i class="fa-solid fa-plus"></i> افزودن دکمه</button><small class="studio-telegram-buttons-help">برای هر دکمه متن، لینک و سبک را انتخاب کن. تلگرام رنگ دلخواه آزاد را نمی‌پذیرد و فقط سبک‌های استاندارد را اعمال می‌کند.</small></div><div class="video-studio-actions" style="padding:0 16px 16px"><button class="studio-btn primary" type="button" id="save-prompt-mother"><i class="fa-solid fa-check"></i> ثبت پرامپت‌ها</button></div></div></div></div>
          <div class="video-studio-actions"><button class="studio-btn" type="button" onclick="submitStudioForm('{{ route('admin.video-studio.jobs.store') }}','POST',false)"><i class="fa-solid fa-list"></i> ذخیره و افزودن به لیست</button><button id="queue-submit" class="studio-btn primary" type="button" onclick="submitStudioForm('{{ route('admin.video-studio.jobs.store') }}','POST',true)"><i class="fa-solid fa-clapperboard"></i> افزودن به لیست و ساخت ویدیو</button></div>
          <small>گزینهٔ اول سفارش را فقط در صف ذخیره می‌کند؛ گزینهٔ دوم سفارش را در صف گذاشته و بلافاصله برای ساخت به ورکفلو می‌فرستد.</small>
        </form>
      </section>

      <section class="studio-card studio-panel studio-telegram-live" id="telegram-live-preview">
        <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-brands fa-telegram"></i> نمایش در کانال</div><div class="studio-panel-meta">پیش‌نمایش زندهٔ خروجی تلگرام</div></div>
        <div class="studio-telegram-preview-grid">
          <div class="studio-phone" aria-label="پیش‌نمایش پیام تلگرام"><div class="studio-phone-notch"></div><div class="studio-phone-head"><div class="studio-phone-avatar"><i class="fa-brands fa-telegram"></i></div><span>کانال وطن</span></div><div class="studio-phone-chat"><div class="studio-phone-post"><div class="studio-phone-media" id="telegram-preview-media">@forelse($productImages as $image)<img src="{{ $image['url'] }}" alt="تصویر محصول">@empty<div class="studio-empty">تصویر محصول</div>@endforelse</div><div class="studio-phone-caption" id="telegram-preview-caption">{{ $settings->caption_text ?: ($selectedProduct?->name_fa ?? 'متن کپشن تلگرام') }}</div></div><div class="studio-phone-buttons" id="telegram-preview-buttons"></div></div></div>
          <div class="studio-telegram-preview-note"><strong>نمایش واقعی پیام کانال</strong><br>تصویرها، کپشن و دکمه‌های انتخاب‌شده را هم‌زمان می‌بینی. هر تغییر بلافاصله در قاب گوشی اعمال می‌شود.<div class="studio-telegram-caption-editor"><label for="telegram-caption-editor">کپشن تلگرام</label><textarea class="studio-textarea" id="telegram-caption-editor" placeholder="کپشن اختصاصی کانال تلگرام...">{{ old('telegram_caption_text', $settings->telegram_caption_text ?? $settings->caption_text ?? ($selectedProduct?->name_fa ?? 'متن کپشن تلگرام')) }}</textarea></div><div class="studio-telegram-live-actions"><button class="studio-btn" type="button" id="open-telegram-settings"><i class="fa-solid fa-sliders"></i> تنظیم کپشن و دکمه‌ها</button></div></div>
        </div>
      </section>
    </div>

      <div class="studio-library-grid">
      <section class="studio-card studio-panel">
        <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-lightbulb"></i> کتابخانه هوک</div><div class="studio-panel-meta">ایده‌های قابل استفاده برای هوش مصنوعی</div></div>
        <form class="studio-form" method="POST" action="{{ route('admin.video-studio.hooks.store') }}" style="margin-bottom:14px">
          @csrf
          <input type="hidden" name="product_id" value="{{ $selectedProduct?->id ?? (int) request()->query('product_id') }}">
          <div class="studio-field"><label for="hook-title">عنوان ایده</label><input id="hook-title" class="studio-input" name="title" required placeholder="مثلاً: سؤال چالشی قبل از خرید"></div>
          <div class="studio-field"><label for="hook-text">متن هوک</label><textarea id="hook-text" class="studio-textarea" name="hook_text" required placeholder="متن کوتاه و الهام‌بخش هوک..."></textarea></div>
          <div class="studio-field"><label for="hook-tags">برچسب‌ها</label><input id="hook-tags" class="studio-input" name="tags" placeholder="کودک، هدیه، مقایسه"></div>
          <button class="studio-btn" type="submit"><i class="fa-solid fa-plus"></i> افزودن هوک</button>
        </form>
        <div class="studio-hook-list">
          @forelse($hookInspirations as $hook)
            <div class="studio-hook"><div class="studio-hook-top"><div class="studio-hook-title">{{ $hook->title }}</div><div class="studio-job-actions"><details><summary class="studio-job-edit-toggle">ویرایش</summary><form method="POST" action="{{ route('admin.video-studio.hooks.update', $hook) }}" class="studio-form" style="margin-top:8px">@csrf @method('PATCH')<input class="studio-input" name="title" value="{{ $hook->title }}" required><textarea class="studio-textarea" name="hook_text" required>{{ $hook->hook_text }}</textarea><input class="studio-input" name="tags" value="{{ $hook->tags }}" placeholder="کودک، هدیه، مقایسه"><button class="studio-btn" type="submit">ذخیره هوک</button></form></details><form method="POST" action="{{ route('admin.video-studio.hooks.destroy', $hook) }}" onsubmit="return confirm('این هوک حذف شود؟')">@csrf @method('DELETE')<button class="studio-link-btn" type="submit" title="حذف"><i class="fa-solid fa-trash"></i></button></form></div></div><div class="studio-hook-text">{{ $hook->hook_text }}</div>@if($hook->tags)<div class="studio-hook-tags"># {{ $hook->tags }}</div>@endif</div>
          @empty
            <div class="studio-empty">هنوز ایده‌ای ثبت نشده است. چند هوک موفق خودت را اینجا اضافه کن تا هوش مصنوعی از ساختارشان الهام بگیرد.</div>
          @endforelse
        </div>
      </section>

      <section class="studio-card studio-panel">
        <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-music"></i> کتابخانهٔ صدا و ویدیو</div><div class="studio-panel-meta">منابع قابل استفادهٔ مجدد</div></div>
        <form class="studio-form" method="POST" action="{{ route('admin.video-studio.sources.store') }}" enctype="multipart/form-data" style="margin-bottom:14px">
          @csrf
          <div class="studio-field"><label>نام منبع</label><input class="studio-input" name="name" required placeholder="مثلاً: موزیک ترند تابستانی"></div>
          <div class="studio-options" style="grid-template-columns:repeat(2,minmax(0,1fr))"><div class="studio-option"><input id="source-library-music" type="radio" name="type" value="music" checked><label for="source-library-music"><i class="fa-solid fa-music"></i>موزیک</label></div><div class="studio-option"><input id="source-library-video" type="radio" name="type" value="video"><label for="source-library-video"><i class="fa-solid fa-film"></i>ویدیوی منبع</label></div></div>
          <input class="studio-input" type="url" name="source_url" placeholder="لینک مستقیم فایل (اختیاری)"><input class="studio-input" type="file" name="source_file" accept="audio/*,video/mp4,video/quicktime,video/webm">
          <button class="studio-btn" type="submit"><i class="fa-solid fa-plus"></i> افزودن منبع</button>
        </form>
        <div class="studio-hook-list">@forelse($sources as $source)<div class="studio-hook"><div class="studio-hook-top"><div class="studio-hook-title">{{ $source->name }}</div><form method="POST" action="{{ route('admin.video-studio.sources.destroy', $source) }}">@csrf @method('DELETE')<button class="studio-link-btn" type="submit" title="حذف"><i class="fa-solid fa-trash"></i></button></form></div><div class="studio-hook-tags">{{ $source->type === 'video' ? 'ویدیوی منبع' : 'موزیک' }} · {{ $source->used_count }} بار استفاده</div></div>@empty<div class="studio-empty">هنوز منبعی ثبت نشده است.</div>@endforelse</div>
      </section>
      </div>

    <div class="studio-modal" id="product-picker" role="dialog" aria-modal="true" aria-labelledby="product-picker-title">
      <div class="studio-modal-card">
        <div class="studio-modal-head"><div class="studio-modal-title" id="product-picker-title">انتخاب محصول برای ساخت ویدیو</div><button class="studio-modal-close" type="button" id="close-product-picker" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="studio-modal-tools"><input class="studio-input" id="product-picker-search" type="search" placeholder="جست‌وجوی نام یا شناسه محصول"><select class="studio-select" id="product-picker-sort"><option value="newest">جدیدترین</option><option value="oldest">قدیمی‌ترین</option><option value="name_asc">نام: الف تا ی</option><option value="name_desc">نام: ی تا الف</option></select></div>
        <div class="studio-product-list" id="product-picker-list">
          @foreach($products as $product)
            @php($doneCount = (int) ($completedVideoCounts[(int) $product->id] ?? 0))
            @php($pendingCount = (int) ($pendingVideoCounts[(int) $product->id] ?? 0))
            @php($covered = $doneCount > 0 || $pendingCount > 0)
            <button type="button" class="studio-product-choice {{ $covered ? 'is-covered' : '' }}" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name_fa }}" data-product-search="{{ mb_strtolower($product->name_fa . ' ' . $product->slug . ' ' . $product->id) }}" data-product-order="{{ optional($product->created_at)->timestamp ?? 0 }}" data-completed-count="{{ $doneCount }}" data-pending-count="{{ $pendingCount }}">
              <span class="studio-product-choice-main"><span class="studio-product-choice-name">{{ $product->name_fa }}</span><span class="studio-product-choice-meta">شناسه {{ $product->id }} · {{ $product->slug }}</span></span>
              @if($doneCount > 0)<span class="studio-product-choice-status">{{ $doneCount }} ویدیو ساخته‌شده @if($pendingCount > 0)<small>+ {{ $pendingCount }} در صف</small>@endif</span>@elseif($pendingCount > 0)<span class="studio-product-choice-status" style="color:var(--warning)">{{ $pendingCount }} در صف ساخت</span>@else<span class="studio-product-choice-meta">آماده ساخت</span>@endif
            </button>
          @endforeach
          <div class="studio-empty" id="product-picker-empty" style="display:none">محصولی با این جست‌وجو پیدا نشد.</div>
        </div>
      </div>
    </div>

    <section class="studio-card studio-panel" id="studio-queue-panel" style="margin-bottom:16px">
      <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-list-check"></i> صف ساخت ویدیو</div><div class="studio-panel-meta">آخرین ۲۰ سفارش</div></div>
      @if($jobs->isNotEmpty())
        <form id="studio-bulk-form" method="POST" action="{{ route('admin.video-studio.jobs.bulk') }}">
          @csrf
          <div class="studio-job-actions" style="margin-bottom:10px"><button class="studio-btn" type="submit" name="action" value="retry"><i class="fa-solid fa-rotate"></i> ساخت مجدد انتخاب‌شده‌ها</button><button class="studio-btn" type="submit" name="action" value="delete"><i class="fa-solid fa-trash"></i> حذف انتخاب‌شده‌ها</button><small class="studio-muted">برای مدیریت گروهی، کنار هر سفارش را تیک بزنید.</small></div>
        </form>
        <div class="studio-queue">
          @foreach($jobs as $job)
            @php($jobStatus = (string) $job->status)
            @php($jobLabel = ['queued' => 'در صف', 'processing' => 'در حال ساخت', 'completed' => 'تکمیل‌شده', 'failed' => 'ناموفق'][$jobStatus] ?? $jobStatus)
            <div class="studio-job">
              <div class="studio-job-main"><label class="studio-check"><input form="studio-bulk-form" type="checkbox" name="job_ids[]" value="{{ $job->id }}"><span></span></label><div><div class="studio-job-title">{{ $job->product?->name_fa ?? 'محصول حذف‌شده' }}</div><div class="studio-job-meta">{{ $job->source_mode === 'video' ? 'ویدیوی منبع' : ($job->source_mode === 'music' ? 'فایل موزیک' : 'منبع خودکار') }} · قاب {{ $job->aspect_ratio }} · {{ \App\Support\Jalali::formatNumeric($job->created_at) }}</div></div></div>
              <div class="studio-job-status {{ in_array($jobStatus, ['queued','processing','completed','failed'], true) ? $jobStatus : 'queued' }}">{{ $jobLabel }}</div>
              <div class="studio-muted">#{{ $job->id }}</div>
              <div class="studio-job-actions"><button type="button" class="studio-job-edit-toggle" data-job-editor-toggle="{{ $job->id }}"><i class="fa-solid fa-pen-to-square"></i> ویرایش</button>@if(in_array($jobStatus, ['queued','failed'], true))<form method="POST" action="{{ route('admin.video-studio.jobs.retry', $job) }}">@csrf<button class="studio-link-btn" type="submit" title="{{ $jobStatus === 'queued' ? 'ساخت' : 'ساخت مجدد' }}"><i class="fa-solid {{ $jobStatus === 'queued' ? 'fa-play' : 'fa-rotate-left' }}"></i> {{ $jobStatus === 'queued' ? 'ساخت' : 'ساخت مجدد' }}</button></form>@endif</div>
              <div class="studio-job-editor is-hidden" id="studio-job-editor-{{ $job->id }}"><small>اصلاحیه را محاوره‌ای بنویس؛ هوش مصنوعی آن را روی همین سفارش اعمال می‌کند.</small><form method="POST" action="{{ route('admin.video-studio.jobs.revise', $job) }}" class="studio-form">@csrf<textarea class="studio-textarea" name="revision_request" required placeholder="مثلاً: هوک کوتاه‌تر و هیجان‌انگیزتر شود، هر دو تصویر استفاده شوند و کپشن دوستانه‌تر باشد."></textarea><div class="studio-job-actions"><button class="studio-btn primary" type="submit"><i class="fa-solid fa-wand-magic-sparkles"></i> ارسال اصلاحیه و ساخت مجدد</button>@if($job->video_url)<a class="studio-btn" href="{{ $job->video_url }}" target="_blank" rel="noopener"><i class="fa-solid fa-video"></i> مشاهده خروجی</a>@endif</div></form></div>
            </div>
          @endforeach
        </div>
      @else
        <div class="studio-empty">هنوز سفارشی در صف ساخت نیست. تنظیمات را انتخاب کنید و «ذخیره و افزودن به صف ساخت» را بزنید.</div>
      @endif
    </section>

    <section class="studio-card studio-panel" id="studio-produced-panel" style="margin-bottom:16px">
      <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-shield-check"></i> محصولات دارای سفارش ساخت</div><div class="studio-panel-meta">برای جلوگیری از ساخت تکراری</div></div>
      @if($producedProducts->isNotEmpty())
        <div class="studio-table-wrap"><table class="studio-table"><thead><tr><th>محصول</th><th>آخرین وضعیت</th><th>شماره سفارش</th><th>تاریخ</th></tr></thead><tbody>
          @foreach($producedProducts as $produced)
            @php($pStatus=(string)$produced->status)
            <tr><td class="studio-product">{{ $produced->product?->name_fa ?? 'محصول حذف‌شده' }}</td><td><span class="studio-badge {{ $pStatus === 'completed' ? 'success' : ($pStatus === 'failed' ? 'danger' : 'warning') }}">{{ $pStatus === 'completed' ? 'تکمیل‌شده' : ($pStatus === 'failed' ? 'ناموفق' : 'در صف/در حال ساخت') }}</span></td><td>#{{ $produced->id }}</td><td>{{ \App\Support\Jalali::formatNumeric($produced->created_at) }}</td></tr>
          @endforeach
        </tbody></table></div>
      @else
        <div class="studio-empty">هنوز برای محصولی سفارش ساخت ثبت نشده است.</div>
      @endif
    </section>

    <div class="studio-layout" id="studio-system-layout">
      <section class="studio-card studio-panel">
        <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-chart-column"></i> روند اجرای خط تولید</div><div class="studio-panel-meta">۱۴ روز اخیر</div></div>
        @php($maxDaily = max(1, (int) $daily->max('count')))
        @if($daily->sum('count') > 0)
          <div class="studio-chart" aria-label="نمودار اجرای روزانه">
            @foreach($daily as $day)
              <div class="studio-bar"><span class="studio-bar-value">{{ $day['count'] }}</span><span class="studio-bar-fill" style="height:{{ max(3, round(($day['count'] / $maxDaily) * 100)) }}%"></span><span class="studio-bar-label">{{ $day['label'] }}</span></div>
            @endforeach
          </div>
        @else
          <div class="studio-empty">هنوز داده‌ای برای نمودار تولید ویدیو ثبت نشده است.</div>
        @endif
      </section>

      <section class="studio-card studio-panel">
        <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-heart-pulse"></i> سلامت سیستم</div><div class="studio-panel-meta">منابع متصل</div></div>
        <div class="studio-health">
          <div class="studio-health-item"><div class="studio-health-label">نرخ موفقیت</div><div class="studio-health-value">{{ $videoCount ? number_format(($completedCount / $videoCount) * 100, 1) : '۰' }}٪</div><div class="studio-progress"><span style="width:{{ $videoCount ? min(100, ($completedCount / $videoCount) * 100) : 0 }}%"></span></div></div>
          <div class="studio-health-item"><div class="studio-health-label">پوشش محصولات فعال</div><div class="studio-health-value">{{ $activeProducts ? number_format(($coveredProducts / $activeProducts) * 100, 1) : '۰' }}٪</div><div class="studio-progress"><span style="width:{{ $activeProducts ? min(100, ($coveredProducts / $activeProducts) * 100) : 0 }}%"></span></div></div>
        </div>
        <div style="margin-top:16px">
          <div class="studio-source"><div class="studio-source-icon"><i class="fa-solid fa-database"></i></div><div class="studio-source-name">محصولات دیتابیس</div><div class="studio-source-status">متصل</div></div>
          <div class="studio-source"><div class="studio-source-icon"><i class="fa-solid fa-video"></i></div><div class="studio-source-name">ثبت تولیدهای ویدیو</div><div class="studio-source-status">{{ $dataSources['generated_videos'] ? 'متصل' : 'در انتظار جدول' }}</div></div>
          <div class="studio-source"><div class="studio-source-icon"><i class="fa-solid fa-flask"></i></div><div class="studio-source-name">آزمایش‌های محصول</div><div class="studio-source-status">{{ $dataSources['product_test_runs'] ? 'متصل' : 'در انتظار جدول' }}</div></div>
        </div>
      </section>
    </div>

    <section class="studio-card studio-panel" id="studio-latest-videos-panel" style="margin-bottom:16px">
      <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-clock-rotate-left"></i> آخرین خروجی‌های ویدیو</div><div class="studio-panel-meta">دادهٔ زنده از دیتابیس</div></div>
      @if($latestVideos->isNotEmpty())
        <div class="studio-table-wrap"><table class="studio-table"><thead><tr><th>محصول</th><th>وضعیت</th><th>مدت</th><th>کیفیت</th><th>تاریخ</th></tr></thead><tbody>
          @foreach($latestVideos as $video)
            @php($status = (string) $video->status)
            @php($statusClass = in_array($status, ['completed','success'], true) ? 'success' : (in_array($status, ['failed','error'], true) ? 'danger' : 'warning'))
            <tr><td><div class="studio-product">{{ $video->product?->name_fa ?? 'بدون محصول' }}</div><div class="studio-muted">#{{ $video->id }}</div></td><td><span class="studio-badge {{ $statusClass }}"><i class="fa-solid {{ $statusClass === 'success' ? 'fa-check' : ($statusClass === 'danger' ? 'fa-xmark' : 'fa-ellipsis') }}"></i>{{ $status === 'completed' ? 'موفق' : ($status === 'failed' || $status === 'error' ? 'ناموفق' : 'در حال پردازش') }}</span></td><td>{{ $video->duration_seconds ? $video->duration_seconds . ' ثانیه' : '—' }}</td><td>{{ $video->width && $video->height ? $video->width . '×' . $video->height : '—' }}</td><td>{{ \App\Support\Jalali::formatNumeric($video->created_at) }}</td></tr>
          @endforeach
        </tbody></table></div>
      @else
        <div class="studio-empty">هنوز خروجی ویدیویی در دیتابیس ثبت نشده است. ثبت‌های پایپ‌لاین تلگرام فعلاً در شیت گزارش ذخیره می‌شوند.</div>
      @endif
    </section>

    <section class="studio-card studio-panel" id="studio-latest-tests-panel">
      <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-vials"></i> آخرین آزمایش‌های محصول</div><a class="studio-panel-meta" href="{{ route('admin.product-tests.history') }}">مشاهده همه ←</a></div>
      @if($latestTests->isNotEmpty())
        <div class="studio-table-wrap"><table class="studio-table"><thead><tr><th>محصول</th><th>مدل</th><th>وضعیت</th><th>زمان اجرا</th><th>تاریخ</th></tr></thead><tbody>
          @foreach($latestTests as $test)
            @php($testStatus = (string) $test->status)
            <tr><td class="studio-product">{{ $test->product?->name_fa ?? 'پیش‌نویس محصول' }}</td><td>{{ $test->model_id }}</td><td><span class="studio-badge {{ $testStatus === 'completed' ? 'success' : ($testStatus === 'failed' ? 'danger' : 'warning') }}">{{ $testStatus === 'completed' ? 'موفق' : ($testStatus === 'failed' ? 'ناموفق' : 'در حال اجرا') }}</span></td><td>{{ $test->duration_ms ? number_format($test->duration_ms / 1000, 1) . ' ثانیه' : '—' }}</td><td>{{ \App\Support\Jalali::formatNumeric($test->created_at) }}</td></tr>
          @endforeach
        </tbody></table></div>
      @else
        <div class="studio-empty">هنوز آزمایش محصولی ثبت نشده است.</div>
      @endif
    </section>
  </div>
</main>
@endsection

@section('scripts')
<script>
  document.getElementById('breadcrumb')?.replaceChildren(document.createTextNode('تولید خودکار ویدیو'));
  const sourceHelp = document.getElementById('source-help');
  const sourceUrlField = document.getElementById('source-url-field');
  const sourceUrl = document.getElementById('source-url');
  const sourceLibrary = document.getElementById('source-library');
  const keywordToggle = document.getElementById('auto-keyword-toggle');
  const keywordSettings = document.getElementById('keyword-settings');
  const hookToggle = document.querySelector('input[name="auto_generate_hook"][type="checkbox"]');
  const captionToggle = document.querySelector('input[name="auto_generate_caption"][type="checkbox"]');
  const hookManual = document.getElementById('hook-manual');
  const captionManual = document.getElementById('caption-manual');
  const productPicker = document.getElementById('product-picker');
  const productPickerSearch = document.getElementById('product-picker-search');
  const productPickerSort = document.getElementById('product-picker-sort');
  const productPickerList = document.getElementById('product-picker-list');
  const productPickerEmpty = document.getElementById('product-picker-empty');
  const studioForm = document.getElementById('studio-settings-form');
  const studioProduct = document.getElementById('studio-product');
  const promptMotherModal = document.getElementById('prompt-mother-modal');
  const promptFallback = document.getElementById('prompt-profile-fallback');
  const instagramPrompt = document.getElementById('instagram-prompt');
  const formMethod = document.getElementById('studio-form-method');
  // بعضی کش‌های قدیمی ممکن است مقدار hidden محصول را حذف کنند؛ شناسهٔ آدرس همیشه منبع پشتیبان است.
  if (studioProduct && !studioProduct.value) {
    const queryProductId = new URLSearchParams(window.location.search).get('product_id');
    if (queryProductId) studioProduct.value = queryProductId;
  }
  const sourceDescriptions = {
    auto: 'ورکفلو بر اساس منبع موجود، بهترین گزینه را انتخاب می‌کند.',
    upload: 'یک فایل مستقیم صوتی یا ویدیویی را با نشانی آن مشخص کنید.',
    music: 'فایل موزیک انتخابی شما روی ویدیوی محصول قرار می‌گیرد.',
    video: 'صدای ویدیوی منبع استخراج می‌شود و مدت خروجی با آن هماهنگ می‌ماند.'
  };
  function updateStudioControls() {
    const selected = document.querySelector('input[name="source_mode"]:checked')?.value || 'auto';
    if (sourceHelp) sourceHelp.textContent = sourceDescriptions[selected] || sourceDescriptions.auto;
    if (sourceUrlField) sourceUrlField.style.display = selected === 'auto' ? 'none' : 'grid';
    if (sourceUrl) sourceUrl.placeholder = selected === 'video' ? 'لینک ویدیوی منبع' : 'لینک فایل صوتی یا موزیک';
    if (hookManual) hookManual.classList.toggle('is-hidden', !!hookToggle?.checked);
    if (captionManual) captionManual.classList.toggle('is-hidden', !!captionToggle?.checked);
    if (keywordSettings) keywordSettings.classList.toggle('is-hidden', !!keywordToggle?.checked);
  }
  document.querySelectorAll('input[name="source_mode"]').forEach((input) => input.addEventListener('change', updateStudioControls));
  sourceLibrary?.addEventListener('change', () => {
    const type = sourceLibrary.options[sourceLibrary.selectedIndex]?.dataset.sourceType;
    if (type) document.querySelector(`input[name="source_mode"][value="${type}"]`)?.click();
  });
  keywordToggle?.addEventListener('change', updateStudioControls);
  hookToggle?.addEventListener('change', updateStudioControls);
  captionToggle?.addEventListener('change', updateStudioControls);
  updateStudioControls();

  function submitStudioForm(action, method) {
    if (!studioForm) return;
    if (promptFallback && instagramPrompt) promptFallback.value = instagramPrompt.value;
    const queueButton = document.getElementById('queue-submit');
    studioForm.action = action;
    studioForm.method = 'POST';
    if (formMethod) formMethod.value = method === 'PATCH' ? 'PATCH' : '';
    if (method === 'POST') { const buildNow = arguments[2] === true; const buildField = document.getElementById('build-now'); if (buildField) buildField.value = buildNow ? '1' : '0'; if (queueButton) { queueButton.disabled = true; queueButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> در حال ثبت سفارش...'; } }
    studioForm.submit();
  }
  window.submitStudioForm = submitStudioForm;
  document.querySelectorAll('#open-prompt-mother,#open-prompt-mother-top').forEach((button) => button.addEventListener('click', () => promptMotherModal?.classList.add('is-open')));
  document.getElementById('close-prompt-mother')?.addEventListener('click', () => promptMotherModal?.classList.remove('is-open'));
  document.getElementById('save-prompt-mother')?.addEventListener('click', () => { if (promptFallback && instagramPrompt) promptFallback.value = instagramPrompt.value; promptMotherModal?.classList.remove('is-open'); });
  const telegramButtonList = document.getElementById('telegram-button-list');
  const telegramCaptionEditor = document.getElementById('telegram-caption-editor');
  const telegramCaptionHidden = document.getElementById('telegram-caption-hidden');
  const defaultTelegramProductLink = @json($selectedProduct ? route('app.product', ['product' => $selectedProduct->route_slug]) : '');
  const telegramButtonWidthSelect = () => '<select class="studio-select" name="telegram_button_width[]"><option value="full">عرض کامل</option><option value="half">نیم‌عرض</option></select>';
  telegramButtonList?.querySelectorAll('[data-telegram-button-row]').forEach((row) => { if (!row.querySelector('[name="telegram_button_width[]"]')) row.insertAdjacentHTML('beforeend', telegramButtonWidthSelect()); const remove = row.querySelector('[data-remove-telegram-button]'); if (remove) row.appendChild(remove); });
  const firstTelegramUrl = telegramButtonList?.querySelector('[name="telegram_button_url[]"]');
  if (firstTelegramUrl && !firstTelegramUrl.value && defaultTelegramProductLink) firstTelegramUrl.value = defaultTelegramProductLink;
  const firstTelegramLabel = telegramButtonList?.querySelector('[name="telegram_button_label[]"]');
  if (firstTelegramLabel && !firstTelegramLabel.value) firstTelegramLabel.value = 'مشاهده محصول';
  const telegramButtonRow = () => { const row = document.createElement('div'); row.className = 'studio-telegram-button-row'; row.dataset.telegramButtonRow = ''; row.innerHTML = '<input class="studio-input" name="telegram_button_label[]" placeholder="متن دکمه، مثلاً مشاهده محصول"><input class="studio-input" type="url" name="telegram_button_url[]" placeholder="https://..."><select class="studio-select" name="telegram_button_style[]"><option value="primary">آبی اصلی</option><option value="success">سبز موفق</option><option value="danger">قرمز هشدار</option></select>' + telegramButtonWidthSelect() + '<button class="studio-telegram-button-remove" type="button" data-remove-telegram-button aria-label="حذف دکمه"><i class="fa-solid fa-trash"></i></button>'; return row; };
  document.getElementById('add-telegram-button')?.addEventListener('click', () => { if (telegramButtonList && telegramButtonList.querySelectorAll('[data-telegram-button-row]').length < 8) telegramButtonList.appendChild(telegramButtonRow()); });
  telegramButtonList?.addEventListener('click', (event) => { const remove = event.target.closest('[data-remove-telegram-button]'); if (remove) remove.closest('[data-telegram-button-row]')?.remove(); });
  function updateTelegramPreview() {
    const captionEditor = document.querySelector('[data-preview-tabs="caption"] .studio-preview-option.is-selected textarea');
    const telegramOptionEditor = telegramCaptionHolder?.querySelector('.studio-preview-option.is-selected textarea');
    const fallbackCaption = document.querySelector('[name="caption_text"]')?.value || '';
    const caption = telegramOptionEditor?.value?.trim() || telegramCaptionEditor?.value?.trim() || captionEditor?.value || fallbackCaption || 'متن کپشن تلگرام';
    if (telegramCaptionHidden) { telegramCaptionHidden.value = caption; telegramCaptionHidden.setAttribute('value', caption); }
    const captionTarget = document.getElementById('telegram-preview-caption');
    if (captionTarget) captionTarget.textContent = caption;
    const target = document.getElementById('telegram-preview-buttons');
    if (!target) return;
    const post = target.closest('.studio-phone-chat')?.querySelector('.studio-phone-post');
    let comments = post?.querySelector('.studio-phone-comments');
    if (post && !comments) { comments = document.createElement('div'); comments.className = 'studio-phone-comments'; comments.innerHTML = '<span>💬</span> دیدگاه‌ها'; post.appendChild(comments); }
    target.replaceChildren();
    telegramButtonList?.querySelectorAll('[data-telegram-button-row]').forEach((row) => {
      const label = row.querySelector('[name="telegram_button_label[]"]')?.value?.trim();
      const url = row.querySelector('[name="telegram_button_url[]"]')?.value?.trim();
      const style = row.querySelector('[name="telegram_button_style[]"]')?.value || 'primary';
      const width = row.querySelector('[name="telegram_button_width[]"]')?.value || 'full';
      if (!label || !url) return;
      const button = document.createElement('a'); button.className = 'studio-phone-button ' + style + ' ' + width; button.href = url; button.target = '_blank'; button.rel = 'noopener'; button.textContent = label; target.appendChild(button);
    });
  }
  document.addEventListener('input', (event) => { if (event.target.closest('#telegram-button-list,[data-preview-tabs="caption"],[data-telegram-caption-options],[name="caption_text"],#telegram-caption-editor')) updateTelegramPreview(); });
  document.addEventListener('change', (event) => { if (event.target.closest('#telegram-button-list,[data-preview-tabs="caption"]')) updateTelegramPreview(); });
  setTimeout(updateTelegramPreview, 500);

  function sortProductChoices() {
    if (!productPickerList) return;
    const items = [...productPickerList.querySelectorAll('.studio-product-choice')];
    const sort = productPickerSort?.value || 'newest';
    items.sort((a, b) => {
      if (sort === 'name_asc') return a.dataset.productName.localeCompare(b.dataset.productName, 'fa');
      if (sort === 'name_desc') return b.dataset.productName.localeCompare(a.dataset.productName, 'fa');
      const delta = Number(a.dataset.productOrder || 0) - Number(b.dataset.productOrder || 0);
      return sort === 'oldest' ? -delta : delta;
    });
    items.forEach((item) => productPickerList.insertBefore(item, productPickerEmpty));
  }
  function filterProductChoices() {
    const term = (productPickerSearch?.value || '').trim().toLocaleLowerCase('fa');
    let visible = 0;
    productPickerList?.querySelectorAll('.studio-product-choice').forEach((item) => {
      const show = !term || (item.dataset.productSearch || '').toLocaleLowerCase('fa').includes(term);
      item.style.display = show ? 'flex' : 'none';
      if (show) visible++;
    });
    if (productPickerEmpty) productPickerEmpty.style.display = visible ? 'none' : 'block';
  }
  document.getElementById('open-product-picker')?.addEventListener('click', () => { productPicker?.classList.add('is-open'); productPickerSearch?.focus(); sortProductChoices(); filterProductChoices(); });
  document.getElementById('close-product-picker')?.addEventListener('click', () => productPicker?.classList.remove('is-open'));
  productPicker?.addEventListener('click', (event) => { if (event.target === productPicker) productPicker.classList.remove('is-open'); });
  productPickerSearch?.addEventListener('input', filterProductChoices);
  productPickerSort?.addEventListener('change', () => { sortProductChoices(); filterProductChoices(); });
  productPickerList?.querySelectorAll('.studio-product-choice').forEach((item) => item.addEventListener('click', () => {
    window.location = '{{ route('admin.products.dashboard') }}?product_id=' + encodeURIComponent(item.dataset.productId) + '&preview=1';
  }));
  document.getElementById('random-product-picker')?.addEventListener('click', () => {
    const items = [...(productPickerList?.querySelectorAll('.studio-product-choice') || [])];
    if (!items.length) return;
    const fresh = items.filter((item) => Number(item.dataset.completedCount || 0) === 0 && Number(item.dataset.pendingCount || 0) === 0);
    const withoutPending = items.filter((item) => Number(item.dataset.pendingCount || 0) === 0);
    const pool = fresh.length ? fresh : (withoutPending.length ? withoutPending : items);
    const selected = pool[Math.floor(Math.random() * pool.length)];
    window.location = '{{ route('admin.products.dashboard') }}?product_id=' + encodeURIComponent(selected.dataset.productId) + '&preview=1';
  });
  document.querySelectorAll('[data-job-editor-toggle]').forEach((button) => button.addEventListener('click', () => {
    const editor = document.getElementById('studio-job-editor-' + button.dataset.jobEditorToggle);
    editor?.classList.toggle('is-hidden');
  }));
  const previewButton = document.getElementById('generate-preview');
  const previewStatus = document.getElementById('preview-status');
  const previewHidden = { hook: document.getElementById('preview-hook'), caption: document.getElementById('preview-caption'), keyword: document.getElementById('preview-keyword') };
  const previewToggles = { hook: hookToggle, caption: captionToggle, keyword: keywordToggle };
  let telegramCaptionHolder = document.querySelector('[data-telegram-caption-options]');
  let telegramCaptionToggle = document.getElementById('telegram-auto-caption');
  const telegramNote = document.querySelector('.studio-telegram-preview-note');
  if (telegramNote && !telegramCaptionHolder) {
    const smart = document.createElement('div'); smart.className = 'studio-telegram-caption-smart';
    smart.innerHTML = '<div class="studio-telegram-caption-smart-head"><label><input id="telegram-auto-caption" type="checkbox" checked> ساخت کپشن تلگرام با هوش مصنوعی</label><button class="studio-regenerate" type="button" data-regenerate-telegram>ساخت مجدد</button></div><small class="studio-preview-status">دو پیشنهاد کپشن برای کانال تلگرام را ببین و یکی را انتخاب کن.</small><div class="studio-preview-options" data-telegram-caption-options></div>';
    const editor = telegramNote.querySelector('.studio-telegram-caption-editor'); telegramNote.insertBefore(smart, editor || telegramNote.firstChild);
    telegramCaptionHolder = smart.querySelector('[data-telegram-caption-options]'); telegramCaptionToggle = smart.querySelector('#telegram-auto-caption');
  }
  const telegramButtonSettings = document.querySelector('.studio-telegram-buttons');
  if (telegramNote && telegramButtonSettings) {
    telegramNote.insertBefore(telegramButtonSettings, telegramNote.querySelector('.studio-telegram-live-actions') || null);
    telegramButtonSettings.querySelectorAll('input,select,textarea').forEach((field) => field.setAttribute('form', 'studio-settings-form'));
  }
  document.getElementById('open-telegram-settings')?.addEventListener('click', () => {
    telegramButtonSettings?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    telegramButtonSettings?.querySelector('input[name="telegram_button_label[]"]')?.focus();
  });
  function syncPreviewSelection(kind) {
    const holder = document.querySelector('[data-preview-tabs="' + kind + '"]');
    const selected = holder?.querySelector('.studio-preview-option.is-selected textarea');
    if (selected && previewHidden[kind]) previewHidden[kind].value = selected.value;
  }
  function updatePreviewEditability() {
    Object.entries(previewToggles).forEach(([kind, toggle]) => document.querySelector('[data-preview-tabs="' + kind + '"]')?.querySelectorAll('textarea').forEach((editor) => { editor.readOnly = !!toggle?.checked; }));
    telegramCaptionHolder?.querySelectorAll('textarea').forEach((editor) => { editor.readOnly = telegramCaptionToggle?.checked !== false; });
  }
  function renderLoadingOptions(kind, message = 'در حال آماده‌سازی...') {
    const holder = document.querySelector('[data-preview-tabs="' + kind + '"]'); if (!holder) return;
    holder.dataset.hasOptions = '0'; holder.replaceChildren();
    for (let index = 0; index < 3; index++) {
      const option = document.createElement('div'); option.className = 'studio-preview-option is-loading' + (index === 0 ? ' is-selected' : '');
      const title = document.createElement('div'); title.className = 'studio-preview-option-check'; title.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> گزینه ' + (index + 1);
      const editor = document.createElement('textarea'); editor.className = 'studio-textarea'; editor.placeholder = message; editor.value = '';
      option.append(title, editor); holder.appendChild(option);
    }
  }
  function renderPreviewTabs(kind, values) {
    const holder = document.querySelector('[data-preview-tabs="' + kind + '"]'); if (!holder) return;
    const normalized = Array.isArray(values) ? values.slice(0, 3) : [];
    while (normalized.length < 3) normalized.push(normalized[0] || '');
    holder.dataset.hasOptions = '1'; holder.replaceChildren();
    normalized.forEach((value, index) => {
      const option = document.createElement('div'); option.className = 'studio-preview-option' + (index === 0 ? ' is-selected' : ''); option.dataset.previewKind = kind;
      const title = document.createElement('div'); title.className = 'studio-preview-option-check'; title.innerHTML = '<i class="fa-solid fa-check"></i> گزینه ' + (index + 1);
      const editor = document.createElement('textarea'); editor.className = 'studio-textarea'; editor.value = value || ''; editor.readOnly = !!previewToggles[kind]?.checked;
      editor.addEventListener('input', () => { if (option.classList.contains('is-selected') && previewHidden[kind]) previewHidden[kind].value = editor.value; updateTelegramPreview(); });
      option.addEventListener('click', (event) => { if (event.target === editor && !editor.readOnly) return; holder.querySelectorAll('.studio-preview-option').forEach((item) => item.classList.remove('is-selected')); option.classList.add('is-selected'); syncPreviewSelection(kind); updateTelegramPreview(); });
      option.append(title, editor); holder.appendChild(option); if (index === 0 && previewHidden[kind]) previewHidden[kind].value = value || '';
    });
    updatePreviewEditability(); updateStudioControls(); updateTelegramPreview();
  }
  function renderTelegramCaptionOptions(values) {
    if (!telegramCaptionHolder) return;
    const normalized = Array.isArray(values) ? values.slice(0, 2) : [];
    while (normalized.length < 2) normalized.push(normalized[0] || '');
    telegramCaptionHolder.dataset.hasOptions = '1'; telegramCaptionHolder.replaceChildren();
    normalized.forEach((value, index) => {
      const option = document.createElement('div'); option.className = 'studio-preview-option' + (index === 0 ? ' is-selected' : '');
      const title = document.createElement('div'); title.className = 'studio-preview-option-check'; title.innerHTML = '<i class="fa-solid fa-check"></i> گزینه ' + (index + 1);
      const editor = document.createElement('textarea'); editor.className = 'studio-textarea'; editor.value = value || ''; editor.readOnly = telegramCaptionToggle?.checked !== false;
      editor.addEventListener('input', () => { if (option.classList.contains('is-selected')) { if (telegramCaptionEditor) telegramCaptionEditor.value = editor.value; updateTelegramPreview(); } });
      option.addEventListener('click', (event) => { if (event.target === editor && !editor.readOnly) return; telegramCaptionHolder.querySelectorAll('.studio-preview-option').forEach((item) => item.classList.remove('is-selected')); option.classList.add('is-selected'); if (telegramCaptionEditor) telegramCaptionEditor.value = editor.value; updateTelegramPreview(); });
      option.append(title, editor); telegramCaptionHolder.appendChild(option);
    });
    updatePreviewEditability(); updateTelegramPreview();
  }
  function renderTelegramLoadingOptions(message = 'بعد از انتخاب محصول، کپشن‌های پیشنهادی اینجا نمایش داده می‌شوند.') {
    if (!telegramCaptionHolder) return;
    telegramCaptionHolder.dataset.hasOptions = '0'; telegramCaptionHolder.replaceChildren();
    for (let index = 0; index < 2; index++) {
      const option = document.createElement('div'); option.className = 'studio-preview-option is-loading' + (index === 0 ? ' is-selected' : '');
      const title = document.createElement('div'); title.className = 'studio-preview-option-check'; title.innerHTML = '<i class="fa-solid fa-spinner"></i> گزینه ' + (index + 1);
      const editor = document.createElement('textarea'); editor.className = 'studio-textarea'; editor.placeholder = message; editor.value = '';
      option.append(title, editor); telegramCaptionHolder.appendChild(option);
    }
  }
  async function requestPreview(channel = 'instagram', kind = null) {
    if (!studioForm) return;
    const productId = studioProduct?.value || studioForm.dataset.productId || new URLSearchParams(window.location.search).get('product_id') || '';
    if (!productId) { if (previewStatus) previewStatus.textContent = 'ابتدا یک محصول انتخاب کن.'; return; }
    if (promptFallback && instagramPrompt) promptFallback.value = instagramPrompt.value;
    if (kind) renderLoadingOptions(kind); else ['hook', 'caption', 'keyword'].forEach((item) => renderLoadingOptions(item));
    if (channel === 'telegram' && telegramCaptionHolder) { telegramCaptionHolder.dataset.hasOptions = '0'; telegramCaptionHolder.replaceChildren(); telegramCaptionHolder.innerHTML = '<div class="studio-preview-status"><i class="fa-solid fa-spinner fa-spin"></i> در حال تولید دو کپشن تلگرام...</div>'; }
    if (previewStatus) previewStatus.textContent = 'در حال دریافت پیشنهادهای تازه از هوش مصنوعی...';
    const formPayload = new FormData(studioForm); formPayload.delete('_method'); formPayload.set('product_id', productId); formPayload.set('channel', channel);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value || '';
    const response = await fetch('{{ route('admin.video-studio.preview') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: formPayload });
    const rawResponse = await response.text(); let payload = {}; try { payload = JSON.parse(rawResponse); } catch (parseError) { payload = {}; }
    if (!response.ok) throw new Error((payload.message ? payload.message + ' ' : '') + '(کد پاسخ ' + response.status + ')');
    if (channel === 'telegram') { renderTelegramCaptionOptions(payload.caption_options || payload.caption || payload.telegram_caption_options || []); }
    else if (kind) renderPreviewTabs(kind, payload[kind + '_options']);
    else { renderPreviewTabs('hook', payload.hook_options); renderPreviewTabs('caption', payload.caption_options); renderPreviewTabs('keyword', payload.keyword_options); }
    if (previewStatus) previewStatus.textContent = 'پیشنهادهای تازه آماده شد؛ گزینهٔ انتخاب‌شده قابل استفاده در سفارش ساخت است.';
  }
  async function generatePreview() { if (!studioForm || !previewButton) return; previewButton.disabled = true; previewButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> در حال تولید...'; try { await requestPreview('instagram'); } catch (error) { if (previewStatus) previewStatus.textContent = error.message || 'تولید پیش‌نمایش ناموفق بود.'; } finally { previewButton.disabled = false; previewButton.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> ساخت ۳ پیشنهاد'; } }
  async function regenerateKind(kind) { const button = document.querySelector('[data-regenerate-preview="' + kind + '"]'); if (button) { button.disabled = true; button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; } try { await requestPreview('instagram', kind); } catch (error) { if (previewStatus) previewStatus.textContent = error.message || 'ساخت مجدد ناموفق بود.'; } finally { if (button) { button.disabled = false; button.textContent = 'ساخت مجدد'; } } }
  previewButton?.addEventListener('click', generatePreview);
  document.querySelectorAll('[data-regenerate-preview]').forEach((button) => button.addEventListener('click', (event) => { event.preventDefault(); event.stopPropagation(); regenerateKind(button.dataset.regeneratePreview); }));
  document.querySelector('[data-regenerate-telegram]')?.addEventListener('click', async (event) => { event.preventDefault(); const button = event.currentTarget; button.disabled = true; button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; try { await requestPreview('telegram'); } catch (error) { if (previewStatus) previewStatus.textContent = error.message || 'ساخت کپشن تلگرام ناموفق بود.'; } finally { button.disabled = false; button.textContent = 'ساخت مجدد'; } });
  Object.values(previewToggles).forEach((toggle) => toggle?.addEventListener('change', updatePreviewEditability));
  telegramCaptionToggle?.addEventListener('change', () => { updatePreviewEditability(); updateTelegramPreview(); });
  ['hook', 'caption', 'keyword'].forEach((kind) => renderLoadingOptions(kind, 'بعد از انتخاب محصول، پیشنهادها اینجا نمایش داده می‌شوند.'));
  renderTelegramLoadingOptions();
  const originalSubmitStudioForm = submitStudioForm;
  window.submitStudioForm = function(action, method) { Object.keys(previewHidden).forEach(syncPreviewSelection); originalSubmitStudioForm(action, method, arguments[2]); };
  if (new URLSearchParams(window.location.search).get('preview') === '1' && previewButton) setTimeout(generatePreview, 450);
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') productPicker?.classList.remove('is-open'); });
</script>
@endsection
