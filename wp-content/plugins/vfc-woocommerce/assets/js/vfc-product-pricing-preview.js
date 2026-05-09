(function ($) {
  'use strict';

  function C() {
    return window.VFC_PRODUCT_PRICING || {};
  }

  function decimals() {
    var d = parseInt(C().decimals, 10);
    return isNaN(d) ? 2 : d;
  }

  function round4(n) {
    return Math.round(n * 10000) / 10000;
  }

  function fmtMoney(n) {
    var dash = (C().i18n && C().i18n.dash) || '—';
    if (typeof n !== 'number' || isNaN(n)) {
      return dash;
    }
    var sym = C().currency || '€';
    var dec = decimals();
    return (
      n.toLocaleString(undefined, {
        minimumFractionDigits: dec,
        maximumFractionDigits: dec,
      }) +
      ' ' +
      sym
    );
  }

  function pctTpl(tpl, n) {
    if (!tpl) {
      return '(' + n + '%)';
    }
    return String(tpl).split('{{n}}').join(String(n));
  }

  function readBase() {
    var raw = String($('#_vfc_precio_base').val() || '').trim().replace(',', '.');
    if (raw === '') {
      return null;
    }
    var v = parseFloat(raw);
    return isNaN(v) ? null : v;
  }

  function readPct($input, defVal, maxVal) {
    var raw = String($input.val() || '').trim();
    var useGlobal = raw === '';
    var v = useGlobal ? defVal : Math.round(parseFloat(raw.replace(',', '.')));
    if (isNaN(v)) {
      v = Math.round(defVal);
      useGlobal = true;
    }
    v = Math.max(0, Math.min(maxVal, v));
    return { pct: v, global: useGlobal };
  }

  function rowLabel(prefix, info, arrow) {
    if (info) {
      return prefix + ' ' + info + ' ' + arrow;
    }
    return prefix + ' ' + arrow;
  }

  function updatePreview() {
    var cfg = C();
    var i18n = cfg.i18n || {};
    var dash = i18n.dash || '—';
    var defs = cfg.defaults || { pctEmpresa: 0, pctAlumno: 0 };

    var base = readBase();
    var $pe = $('#_vfc_pct_empresa');
    var $pa = $('#_vfc_pct_alumno');
    var re = readPct($pe, defs.pctEmpresa, 1000);
    var ra = readPct($pa, defs.pctAlumno, 100);

    var $hint = $('#vfc-prev-hint');

    if (base === null || base <= 0) {
      $('#vfc-prev-base').text(dash);
      $('#vfc-prev-emp-eur').text(dash);
      $('#vfc-prev-alu-eur').text(dash);
      $('#vfc-prev-net-total').text(dash);
      $('#vfc-prev-gross-total').text(dash);
      if (i18n.invalidBase) {
        $hint.text(i18n.invalidBase).show();
      } else {
        $hint.hide().text('');
      }
      $('#vfc-prev-row-emp-label').text(
        rowLabel(i18n.empRow || '% empresa', '', i18n.arrowImporte || '→ importe')
      );
      $('#vfc-prev-row-alu-label').text(
        rowLabel(i18n.aluRow || '% alumno', '', i18n.arrowImporte || '→ importe')
      );
      return;
    }

    $hint.hide().text('');

    var empEur = round4((base * re.pct) / 100);
    var aluEur = round4((base * ra.pct) / 100);
    var netTotal = round4(base + empEur + aluEur);
    var mult = parseFloat(cfg.grossMultiplier);
    if (isNaN(mult) || mult <= 0) {
      mult = 1;
    }
    var gross = round4(netTotal * mult);

    $('#vfc-prev-base').text(fmtMoney(base));
    $('#vfc-prev-emp-eur').text(fmtMoney(empEur));
    $('#vfc-prev-alu-eur').text(fmtMoney(aluEur));
    $('#vfc-prev-net-total').text(fmtMoney(netTotal));
    $('#vfc-prev-gross-total').text(fmtMoney(gross));

    var empInfo = re.global ? pctTpl(i18n.globalPct, re.pct) : pctTpl(i18n.productPct, re.pct);
    var aluInfo = ra.global ? pctTpl(i18n.globalPct, ra.pct) : pctTpl(i18n.productPct, ra.pct);

    $('#vfc-prev-row-emp-label').text(
      rowLabel(i18n.empRow || '% empresa', empInfo, i18n.arrowImporte || '→ importe')
    );
    $('#vfc-prev-row-alu-label').text(
      rowLabel(i18n.aluRow || '% alumno', aluInfo, i18n.arrowImporte || '→ importe')
    );
  }

  function bind() {
    var $base = $('#_vfc_precio_base');
    var $pe = $('#_vfc_pct_empresa');
    var $pa = $('#_vfc_pct_alumno');
    if (!$base.length || !$pe.length || !$pa.length) {
      return;
    }

    $(document.body).on('input change', '#_vfc_precio_base, #_vfc_pct_empresa, #_vfc_pct_alumno', function () {
      updatePreview();
    });

    $(document.body).on('blur', '.vfc-pct-input', function () {
      var $el = $(this);
      var raw = String($el.val() || '').trim();
      if (raw === '') {
        updatePreview();
        return;
      }
      var mx = parseInt($el.attr('max'), 10);
      if (isNaN(mx)) {
        mx = 100;
      }
      var v = Math.round(parseFloat(raw.replace(',', '.')));
      if (isNaN(v)) {
        $el.val('');
        updatePreview();
        return;
      }
      v = Math.max(0, Math.min(mx, v));
      $el.val(String(v));
      updatePreview();
    });

    updatePreview();
  }

  $(bind);
})(jQuery);
