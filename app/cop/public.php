<?php

require __DIR__.'/portal_data.php';

function getSubCategories($categories, $negcats, $depth) {
  $params = [
    'language' => 'uk',
    'project' => 'wikipedia',
    'depth' => $depth,
    'categories' => implode("\n", $categories),
    'ns' => [14 => 1],
    'format' => 'json',
    'doit' => '1',
    'combination' => 'union'
  ];
  if (!empty($negcats)) {
    $params['negcats'] = implode("\n", $negcats);
  }

  $res = json_decode(file_get_contents('https://petscan.wmflabs.org/?'.http_build_query($params)), true);
  if (empty($res['*']['0']['a']['*'])) {
    return [];
  }

  $cat = array_column($res['*']['0']['a']['*'], 'title');
  $cat = array_map(function ($c) { return str_replace('_', ' ', $c); }, $cat);
  $cat = array_diff($cat, $negcats);

  return $cat;
}

#$copWidget = new Widget('cop', $db);

/*

  */

//$curPortal = key($portals);

if (isset($_GET['portal']) && array_key_exists($_GET['portal'], $portals)) {
  $curPortal = $_GET['portal'];
  $curPortalData = $portals[$curPortal];
  $depth = !empty($curPortalData['depth']) ? $curPortalData['depth'] : 3;

  $igonoreCategories = !empty($curPortalData['ignore']) ? $curPortalData['ignore'] : [];
  $portalCategories = !empty($curPortalData['categories']) ? $curPortalData['categories'] : [$curPortal];
/*  $treeCat = array_fill_keys($portalCategories, []);
  $curCategories = $portalCategories;
  for ($curDepth = 0; $curDepth < $depth; $curDepth++) {
    $allSubCat = [];
    foreach ($curCategories as $category) {
      $subCat = getSubCategories([$category], $igonoreCategories, 0);
      $treeCat[$category] = array_fill_keys($subCat, []);
      $categories = array_merge($categories, $subCat);
      $allSubCat = array_merge($allSubCat, $subCat);
    }

    $curCategories = $allSubCat;
  }
*/
  $categories = $portalCategories;
  $categories = array_merge($categories, getSubCategories($portalCategories, $igonoreCategories, $depth));
  $categories = array_flip($categories);
}

function createTreeSubCat($cat, $ignore, $depth, $origDepth)
{
  $subCat = getSubCategories([$cat], $ignore, 0);
  if (empty($subCat)) {
    return;
  }

  echo str_repeat('--', $origDepth - $depth).'<a href="https://uk.wikipedia.org/wiki/Категорія:'.$cat.'">'.str_replace('_', ' ', $cat).'</a><br/>';
  if ($depth > 0) {
    foreach ($subCat as $c) {
      createTreeSubCat($c, $ignore, $depth - 1, $origDepth);
    }
  }
  else {
    foreach ($subCat as $c) {
      echo str_repeat('--', $origDepth - $depth + 1).str_replace('_', ' ', $c).'<br/>';
    }
  }
}

function createTreeSubCatV2($cat, $ignore, $depth, $origDepth, $parent)
{
  $subCat = getSubCategories([$cat], $ignore, 0);
  if (empty($subCat)) {
    return;
  }

  $newParentIdent = md5($parent.$cat);
  echo '{ "id" : "'.$newParentIdent.'", "parent" : "'.$parent.'", "text" : '.json_encode($cat).' },';

  if ($depth > 0) {
    foreach ($subCat as $c) {
      createTreeSubCatV2($c, $ignore, $depth - 1, $origDepth, $newParentIdent);
    }
  }
  else {
    foreach ($subCat as $c) {
      echo '{ "id" : "'.md5($newParentIdent.$c).'", "parent" : "'.$newParentIdent.'", "text" : '.json_encode($cat).' },';
    }
  }
}
?>
<form action="https://tools.wmflabs.org/dow/">
  <input type="hidden" name="view" value="cop"/>
  <select name="portal">
    <?php
    foreach ($portals as $portal => $categies) {
      echo "<option value='{$portal}' ".($curPortal == $portal ? 'selected' : '').">Портал:{$portal}</option>";
    }
    ?>
  </select>
  <button type="submit">Дивитися</button>
</form>

<div class="row">
  <div class="col-md-6">
  <?php if (!empty($curPortal)): ?>
  <dl class="dl-horizontal">
    <dt>Категорії:</dt>
    <dd><?=implode("\n", $portalCategories)?></dd>
    <dt>Ігноровані:</dt>
    <dd><?=implode("\n", $igonoreCategories)?></dd>
    <dt>Глибина:</dt>
    <dd><?=$depth?></dd>
  </dl>
  <ul style="height: 500px; overflow-y: auto;">
  <?php
    foreach ($categories as $caption => $id) {
      echo '<li id="id'.$id.'"><span>'.$caption.'</span> <a href="#" class="parents_cat">(батьківські)</a></li>';
    }
  ?>
  </ul>
  </div>
  <div class="col-md-6">
    <form id="new_list">
      <div class="form-group">
        <label for="input-category">Категорії:</label>
        <textarea class="form-control" id="input-category" name="category"><?=implode("\n", $portalCategories)?></textarea>
        <p class="help-block">Кожна категорія з нового рядка</p>
      </div>
      <div class="form-group">
        <label for="input-ignore">Ігноровані:</label>
        <textarea class="form-control" id="input-ignore" name="ignore"><?=implode("\n", $igonoreCategories)?></textarea>
        <p class="help-block">Кожна категорія з нового рядка</p>
      </div>
      <div class="form-group">
        <label for="input-depth">Глибина:</label>
        <input type="text" class="form-control" id="input-depth" name="depth" value="<?=$depth?>">
      </div>
      <button type="button" class="btn btn-primary">Порівняти</button>
    </form>
    <table class="table table-condensed" id ="result">
    </table>
  </div>
  <?php endif ?>
</div>

<script>
<?php if (!empty($curPortal)): ?>
  var curCategories = <?=json_encode(array_keys($categories))?>

$( document ).ready(function() {
  $(".parents_cat").on("click", function(e) {
    e.preventDefault();
    var $t = $(this).parents('li');

    var cat = $t.find('span').text();
    var params = {
      "action": "query",
      "format": "json",
      "prop": "categories",
      "titles": "Категорія:" + cat,
      "cllimit": "500",
    }
    $.getJSON("https://uk.wikipedia.org/w/api.php?" + $.param(params) + "&callback=?", function(data){
      var dataCat = data.query.pages;
      dataCat = dataCat[Object.keys(dataCat)[0]];
      var parentsDow = $t.find('.parents-data');
      if (parentsDow.length === 0) {
        $t.append('<ul class="parents-data"></ul>');
        parentsDow = $t.find('.parents-data');
      }

      for (var parentCat in dataCat.categories) {
        var title = dataCat.categories[parentCat].title.substring("Категорія:".length);
        var idCat = curCategories.indexOf(title);
        if (-1 !== idCat) {
          parentsDow.append('<li><a href="javascript:;" onclick="document.location.hash=\'id'+idCat+'\';">'+title+'</a></li>')
        }
      }
    });
  });


  $("#new_list .btn").on('click', function(){
    $(this).prop("disabled", "disabled");
    $.post('/dow/api.php?view=cop&action=get&portal=<?=$curPortal?>', $("#new_list").serialize())
      .done(function(data){
        var ignoreCat = $("#input-ignore").val().split("\n");
        for (var i in ignoreCat) { ignoreCat[i] = ignoreCat[i].trim(); }

        var newCategories = $.parseJSON(data);
        var content = "";
        for (var cat in newCategories) {
          newCategories[cat] = newCategories[cat].replace(/_/g, " ");
          var newCat = newCategories[cat];
          if (-1 === curCategories.indexOf(newCat) && -1 === ignoreCat.indexOf(newCat)) {
            content += "<tr class='success'><td>"+newCat+"</td></tr>";
          }
        }
        for (var cat in curCategories) {
          if (-1 === newCategories.indexOf(curCategories[cat])) {
            content += "<tr class='danger'><td>"+curCategories[cat]+"</td></tr>";
          }
        }
        $("#result").html(content);
        $("#new_list .btn").prop("disabled", null);
      });
  });
});
<?php endif ?>
</script>