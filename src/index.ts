import Export from './components/views/export.vue';

declare namespace panel {
  function plugin(name: string, options: any): void;
}

panel.plugin('rasteiner/export', {
  components: {
    "rs-export-view": Export,
  }
});
