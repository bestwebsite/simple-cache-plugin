import jQuery from 'jquery'; // eslint-disable-line import/no-unresolved

const $enableInMemoryCache = jQuery(document.getElementById('bestwebsite_enable_in_memory_object_caching'));
const inMemoryCacheFields = document.querySelectorAll('.in-memory-cache');

$enableInMemoryCache.on('change', (event) => {
	if (event.target.value === '1') {
		inMemoryCacheFields[0].className =
			inMemoryCacheFields[0].className.replace(/show/i, '') + ' show';
		inMemoryCacheFields[1].className =
			inMemoryCacheFields[1].className.replace(/show/i, '') + ' show';
	} else {
		inMemoryCacheFields[0].className = inMemoryCacheFields[0].className.replace(/show/i, '');
		inMemoryCacheFields[1].className = inMemoryCacheFields[1].className.replace(/show/i, '');
	}
});

const $advancedModeToggle = jQuery(document.getElementById('bestwebsite_advanced_mode'));
const advancedTable = document.querySelectorAll('.sc-advanced-mode-table')[0];
const simpleTable = document.querySelectorAll('.sc-simple-mode-table')[0];
const pageCachingSimple = document.getElementById('bestwebsite_enable_page_caching_simple');
const pageCachingAdvanced = document.getElementById('bestwebsite_enable_page_caching_advanced');
const pageCacheLengthSimple = document.getElementById('bestwebsite_page_cache_length_simple');
const pageCacheLengthAdvanced = document.getElementById('bestwebsite_page_cache_length_advanced');
const pageCacheLengthUnitSimple = document.getElementById('bestwebsite_page_cache_length_unit_simple');
const pageCacheLengthUnitAdvanced = document.getElementById('bestwebsite_page_cache_length_unit_advanced');
const gzipCompressionSimple = document.getElementById('bestwebsite_enable_gzip_compression_simple');
const gzipCompressionAdvanced = document.getElementById('bestwebsite_enable_gzip_compression_advanced');

$advancedModeToggle.on('change', (event) => {
	if (event.target.value === '1') {
		advancedTable.className = advancedTable.className.replace(/show/i, '') + ' show';
		simpleTable.className = simpleTable.className.replace(/show/i, '');

		pageCachingSimple.disabled = true;
		pageCachingAdvanced.disabled = false;

		pageCacheLengthSimple.disabled = true;
		pageCacheLengthAdvanced.disabled = false;

		pageCacheLengthUnitSimple.disabled = true;
		pageCacheLengthUnitAdvanced.disabled = false;

		if (gzipCompressionSimple) {
			gzipCompressionSimple.disabled = true;
			gzipCompressionAdvanced.disabled = false;
		}
	} else {
		simpleTable.className = simpleTable.className.replace(/show/i, '') + ' show';
		advancedTable.className = advancedTable.className.replace(/show/i, '');

		pageCachingSimple.disabled = false;
		pageCachingAdvanced.disabled = true;

		pageCacheLengthSimple.disabled = false;
		pageCacheLengthAdvanced.disabled = true;

		pageCacheLengthUnitSimple.disabled = false;
		pageCacheLengthUnitAdvanced.disabled = true;

		if (gzipCompressionSimple) {
			gzipCompressionSimple.disabled = false;
			gzipCompressionAdvanced.disabled = true;
		}
	}
});
