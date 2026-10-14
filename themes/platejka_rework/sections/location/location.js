(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["location"],{

/***/ "./src/js/components/location-map.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* unused harmony exports createLocationMap, initLocationMap, loadYandexMaps */
const {
  getMapOffsets,
  getShiftedCenter
} = __webpack_require__("./src/js/components/location-map.cjs");
const API_URL = 'https://api-maps.yandex.ru/2.1/?apikey=f0ea8217-775f-491e-bb80-41f935262ea4&lang=ru_RU';
const COORDINATES = [55.921612, 37.534927];
const ICON_URL = 'https://platejka.com/wp-content/uploads/2026/02/map.png';
let apiPromise;
function loadYandexMaps() {
  if (window.ymaps) return Promise.resolve(window.ymaps);
  if (apiPromise) return apiPromise;
  apiPromise = new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-yandex-maps-api]');
    const script = existing || document.createElement('script');
    const onLoad = () => window.ymaps ? resolve(window.ymaps) : reject(new Error('Yandex Maps API is unavailable'));
    script.addEventListener('load', onLoad, {
      once: true
    });
    script.addEventListener('error', () => reject(new Error('Yandex Maps API failed to load')), {
      once: true
    });
    if (!existing) {
      script.src = API_URL;
      script.async = true;
      script.dataset.yandexMapsApi = 'true';
      document.head.append(script);
    }
  });
  return apiPromise;
}
function createLocationMap(element, ymaps) {
  const map = new ymaps.Map(element, {
    center: COORDINATES,
    zoom: 16,
    controls: []
  }, {
    suppressMapOpenBlock: true
  });
  const placemark = new ymaps.Placemark(COORDINATES, {
    balloonContentHeader: '<a href="https://platejka.com/" rel="noopener noreferrer">Платёжка</a><br><span>Международные платежи<br>за 1 день для Вашего бизнеса</span>',
    balloonContentBody: 'г. Москва, Технопарк «Физтехпарк»,<br>Долгопрудненское шоссе, д. 3.<br><a href="tel:+78005338819">+7 800 533-88-19</a><br><a href="mailto:a@platejka.com">a@platejka.com</a>'
  }, {
    iconLayout: 'default#image',
    iconImageHref: ICON_URL,
    iconImageSize: [40, 40],
    iconImageOffset: [-20, -40]
  });
  map.geoObjects.add(placemark);
  const updateOffset = () => {
    map.container.fitToViewport();
    const projection = map.options.get('projection');
    const markerPixels = projection.toGlobalPixels(COORDINATES, map.getZoom());
    map.setGlobalPixelCenter(getShiftedCenter(markerPixels, map.container.getSize(), getMapOffsets(window.innerWidth)));
  };
  updateOffset();
  window.addEventListener('resize', updateOffset, {
    passive: true
  });
  return map;
}
function initLocationMap(element) {
  if (!element || element.dataset.locationMapReady === 'true') return;
  element.dataset.locationMapReady = 'true';
  loadYandexMaps().then(ymaps => ymaps.ready(() => createLocationMap(element, ymaps))).catch(() => {
    element.dataset.locationMapError = 'true';
  });
}
document.querySelectorAll('[data-location-map]').forEach(initLocationMap);


/***/ }),

/***/ "./src/js/components/location-map.cjs":
/***/ ((module) => {

function getMapOffsets(viewportWidth) {
  if (viewportWidth <= 768) return { x: 200, y: 270 };
  if (viewportWidth <= 1230) return { x: 300, y: 350 };
  return { x: 900, y: 350 };
}

function getShiftedCenter(markerPixels, mapSize, offsets) {
  return [
    markerPixels[0] + mapSize[0] / 2 - offsets.x,
    markerPixels[1] + mapSize[1] / 2 - offsets.y,
  ];
}

module.exports = { getMapOffsets, getShiftedCenter };


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/location-map.js"));
/******/ }
]);