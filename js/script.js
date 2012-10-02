var oldval = 0,
    editmode = false,
    selectedchain = 0;

var	chainadd_delay = chainadd_id = chainadd_number = 0;
		chainadd_state = true;
		chainadd_name = "";

$(function() {
  // MODALS
  $('aside#userselect')
    .modal({ backdrop: 'static', keyboard: false, show: false })
    .find('button').on("click", function() {
      name = $(this).attr('id').replace(/user\-/i, '');
      if (name == "cancel") name = "";
      location.href = "/" + name;
    });
  
	$('aside#chainadd')
		.modal({ backdrop: 'static', keyboard: true, show: false })
		.on('show', function() {
			chainadd_number = parseInt($('input#manual-num').val());
			if (isNaN(chainadd_number) || chainadd_number < 1 || chainadd_number > 1024) {
				alert("Bitte zuerst eine gültige Schalternummer wählen!");
				return false;
			}
			$('aside#chainadd h4 small').text("#" + chainadd_number);
			exec({'method': 'list', 'type': 'chains', 'id': userId() }, null, function(d) {
				var cn = $('#chainadd-list-select');
				if (d.success) {
					cn.children().last().remove();
					$(d.data).each(function(i, e) {
						var a = $("<a>").text(e.name + " (" + e['switch-count'] + " Schalter)").attr({ 'id': 'chainadd-list-value-' + e.id, 'data-name': e.name });
						$(cn).append($("<li>").append(a));
					});
				} else alert("error!");
			});
		})
    .find("button#chainadd-save")
      .on("click", function() {
				var postData = {
					method: 'add', type: 'switch',
					id: userId(), chain: chainadd_id, // -1 for new
					name: chainadd_name, delay: chainadd_delay,
					state: chainadd_state ? 1 : 0, number: chainadd_number
				};
				exec(postData, null, function(d) { /*location.reload();*/ });
      }).parent()
    .find("button#chainadd-cancel")
      .on("click", function() {
        $('#chainadd').modal("hide");
				chainadd_delay = chainadd_id = chainadd_number = 0;
				chainadd_state = true;
				chainadd_name = "";
      });

  $('aside#infobox')
    .modal({ backdrop: 'static', keyboard: false, show: false });
    
  $('aside#chainedit')
    .modal({ backdrop: 'static', keyboard: true, show: false })
    .on("hide", function() {
      selectedchain = 0;
    })
    .on("show", function() {
      var postData = { 'type': 'chain', 'method': 'details', 'id': selectedchain };
      $("#chainedit header.modal-header h3").text("Lade Daten...");
      $("#chainedit section.modal-body").empty().text("Bitte warten...");
      $("button#chainedit-save").add("input#chainedit-name").hide();
      exec(postData, null, function(d) {
        var tbl = $('<table class="table table-striped table-bordered">'),
            thd = $('<thead><tr><th>Nummer<th>Status<th>Verzög.<th>&nbsp;</thead>')
            tbd = $('<tbody>'),
            delbtn = $('<button>').attr({ 'class': 'btn btn-danger btn-mini pull-right'}).text("x");
        
        $("#chainedit header.modal-header h3")
          .text(d.data.name)
          .on("click", function() {
            $("input#chainedit-name").val($(this).text()).show();
            $(this).hide();
            $("button#chainedit-save").show();
          });
        $(d.data.switches).each(function(i, e) {
          tbd.append($('<tr>').attr({ id: 'switch-' + e.id })
															.append('<td>' + e.number + '</td>')
                              .append('<td>' + (e.state == "1" ? "An" : "Aus") + '</td>')
                              .append('<td>' + parseInt(e.delay) + ' sek.</td>')
                              .append($('<td>').append(delbtn.clone().attr({ 'id': 'chainedit-deleteswitch-' + e.id }).addClass("chainedit-deleteswitch"))));
        });
        $("#chainedit section.modal-body").empty().append(tbl.append(thd).append(tbd));
        $('#chainedit .chainedit-deleteswitch').on("click", function() {
					var switchnum = parseInt($(this).parent().parent().find('td:first-child').text()),
							switchid = parseInt($(this).parent().parent().attr('id').replace(/switch-/, ''));
          if (confirm("Schalter #" + switchnum + " wirklich aus dieser Chain entfernen?")) {
            var postData = { 'type': 'chain', 'method': 'removeswitch', 'id': selectedchain, number: switchid };
            exec(postData, $(this), function(d) { location.reload(); });
          }
        });
      });
    })
    .find("button#chainedit-save")
      .on("click", function() {
        var nme = $('input#chainedit-name').val().replace(/^\s+/, "").replace(/\s+$/, "");
        if (nme != "") {
          var postData = { 'type': 'chain', 'method': 'rename', 'id': selectedchain, 'name': nme };
          exec(postData, $(this), function(d) { location.reload(); });
        }
      }).parent()
    .find("button#chainedit-cancel")
      .on("click", function() {
        $('#chainedit').modal("hide");
      }).parent()
    .find("button#chainedit-delete")
      .on("click", function() {
        if (confirm("Chain '" + $('button#chain-' + selectedchain).text() + "' wirklich löschen?")) {
          var postData = { 'type': 'chain', 'method': 'delete', 'id': selectedchain };
          exec(postData, $(this), function(d) { location.reload(); });
        }
      });
  
  // NAV-LINKS
  $('#userselectbutton, #userselectlink').on("click", function() {
    $('aside#userselect').modal("show");
    return false;
  });
  
  $('#infolink').on("click", function() {
    $('aside#infobox').modal("show");
    return false;
  });
  
  $('#reloadlink').on("click", function() {
    location.reload();
    return false;
  });
  
  
  // BUTTONS
  $('button#editmode').on("click", function() {
    editmode = !editmode;
    $(".chains .switch, #editmode").toggleClass("btn-success", editmode);
  });
  
  $('.manual button.manual').on("click", function() {
    $('aside#dips').slideToggle("slow");
  });
  
  $('.manual button.add').on("click", function() {
		$('aside#chainadd').modal('show');
  });

	$('#chainadd-delay-select a').on('click', function() {
		var val = $(this).attr('id').replace(/chainadd-delay-value-/, '');
		val = parseInt((val == "custom" ? prompt('Verzögerung in Sekunden?', 0) : val)) | 0;
		$('#chainadd-delay-text').text(val + " sek.");
		chainadd_delay = val;
	});

	$('#chainadd-list-select a').live('click', function() {
		var id = $(this).attr('id').replace(/chainadd-list-value-/, ''),
				name = $(this).attr('data-name');
		if (id == "new") {
			var newname = prompt('Name der neuen Chain?', name);
			if (newname == null) return false;
			chainadd_id = -1;
			chainadd_name = newname;
		} else {
			chainadd_id = id;
			chainadd_name = name;
		}
		$('#chainadd-list-text').text("Chain: " + chainadd_name);
	});
	
	$('button[id^="chainadd-switch-state-"]').on("click", function() {
		chainadd_state = !!($(this).attr('id').match(/on$/));
	});
  
  
  // SWITCHES
  $('.chains .switch').on("click", function() {
    var me = $(this),
        chainId = parseInt(me.attr('id').replace(/chain\-/i, '')),
        postData = { 'type': 'chain', 'method': 'activate', 'id': chainId };
    
    if (editmode) {
      selectedchain = me.attr('id').replace(/chain-/i, "");
      $('aside#chainedit').modal("show");
      
      $('button#editmode').click();
    } else exec(postData, me);
  });
  
  $('.manual .onoff .switch').on("click", function() {
    var state = $(this).hasClass("on"),
        swnum = $('input#manual-num').val(),
        postData = {'type': 'manual', 'method': state, 'id': swnum},
        me = $(this);
    if (isNaN(swnum) || swnum < 1 || 1023 < swnum) return false;
    
    exec(postData, me);
  });
  
  
  // DIP CALLBACK
  $('input#manual-num')
    .on("keyup", function() {
      if (x = tr($(this).val())) $(this).parent().removeClass("error"); else {
        $(this).parent().addClass("error");
        x = "0000000000";
      }
      $('div#bre div').html(x.toDIP() + "<br />1 2 3 4 5 A B C D E");
      $('div#xan div').html(x.reverse().toDIP() + "<br />1 2 3 4 5 6 7 8 9 10");
    })
    .trigger("keyup");
  
  $('div#bre div, div#xan div')
    .on("click", function(e) {
      var tgt = (e.currentTarget || e.target),
          dip = Math.min(Math.floor(Math.min(e.offsetX * 1.05, 199) / 20), 9),
          bin = $(tgt).text().substr(0, 10).fromDIP(),
          val = rtr(bin.flipDIP(dip), ((tgt.parentElement.id || tgt.parentNode.id) == "xan"));
      $('input#manual-num').val(val == 0 ? "" : val).trigger("keyup");
    });
});

function exec(data, btn, cb) {
  if (btn) btn.button('loading');
  $.post("exec.php", data, function(d) {
    if (d && !d.success && d.error) alert(d.error);
    if (cb) cb(d);
    if (btn) btn.button('reset');
  }, "json");
}

function userId() {
	var el = $('.chains.well');
	if (el.length == 0) return false;
	var elid = el.attr('id'),
			usid = parseInt((elid.match(/(\S+)\-(\d+)/i) || [,,])[2] || 0);
	if (!usid || usid <= 0) return false;
	return usid;
}

// ▄▀

String.prototype.reverse = function() { return this.split("").reverse().join(""); }
String.prototype.toDIP = function() { return this.replace(/0/g, "&#9604;").replace(/1/g, "&#9600;"); }
String.prototype.fromDIP = function() { return this.replace(/(▄|\&\#9604\;)/g, "0").replace(/(▀|\&\#9600\;)/g, "1"); }
String.prototype.flipDIP = function(n) { return this.substr(0, n) + (this.substr(n, 1) == "0" ? "1" : "0") + this.substr(++n); }

function tr(n) {
  if (n == "") return "0000000000";
  if (isNaN(n) || n < 1 || 1023 < n) return false;
  n = new Array(10 - Math.floor(Math.log(n) / Math.log(2))).join("0") + parseInt(n, 10).toString(2);
  return n.substr(4) + n.substr(0, 4).reverse();
}
function rtr(n, x) {
  if (n.match(/[^01]/g)) return false;
  if (x) n = n.reverse();
  return parseInt(n.substr(6).reverse() + n.substr(0, 6), 2);
}