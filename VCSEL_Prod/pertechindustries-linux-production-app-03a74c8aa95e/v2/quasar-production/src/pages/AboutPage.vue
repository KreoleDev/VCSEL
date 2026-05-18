<template>
  <q-page>
    <div class="q-pa-md">
      <q-btn label="< Back" @click="loadHome" class="text-white bg-primary" />
      <q-list class="q-mt-md">
        <q-item>
          <q-item-section>
            <q-item-label>Version</q-item-label>
            <q-item-label caption lines="2">{{ version }}</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </div>
  </q-page>
</template>

<script>
import { defineComponent, ref } from "vue";
import { useRouter } from "vue-router";
import packageInfo from "../../package.json";

export default defineComponent({
  name: "AboutPage",
  setup() {
    const router = useRouter();
    return {
      router,
      version: packageInfo.version,
    };
  },
  // ============================================= //
  methods: {
    loadHome: function () {
      this.router.push("/");
    },
    // --------------------------------------------- //
  },
});
</script>
