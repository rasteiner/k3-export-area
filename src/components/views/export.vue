<template>
    <k-inside>
        <k-view>
            <k-header>
                Static Export
                <k-button-group slot="left">
                    <k-button @click="createExport" :disabled="exporting" icon="download">
                        Start a new export
                    </k-button>
                </k-button-group>
            </k-header>

            <table class="rs-exports" v-if="files.length || newExport ">
                <tr>
                    <th>Date</th>
                    <th>Base Path</th>
                    <th>Size</th>
                    <th class="rs-exports__options"></th>
                </tr>
                <tr v-if="newExport">
                    <td>{{ niceDate(newExport.date) }}</td>
                    <td>{{ newExport.basePath }}</td>
                    <td> 
                        <k-progress :value="newExport.status * 100" />
                    </td>
                    <td class="rs-exports__options">
                        <k-options-dropdown :options="[
                            {
                                label: 'Cancel',
                                icon: 'times',
                                action: 'cancel'
                            }
                        ]" />
                    </td>
                </tr>
                <tr v-for="file of sortedFiles" :key="file.id">
                    <td>{{ niceDate(file.date) }}</td>
                    <td>{{ file.basePath }}</td>
                    <td>{{ niceSize(file.size) }}</td>
                    <td class="rs-exports__options">
                        <k-options-dropdown :options="[
                            {icon: 'download', text: 'Download', click: () => downloadFile(file)},
                            {icon: 'trash', text: 'Delete', click: () => deleteFile(file)}
                        ]" />
                    </td>
                </tr>
            </table>

            <k-empty v-else>
                No exports are available yet.
            </k-empty>
        </k-view>
    </k-inside>
</template>

<script>

function splitOnFirst (str, sep) {
  const index = str.indexOf(sep);
  return index < 0 ? [str] : [str.slice(0, index), str.slice(index + sep.length)];
}

async function *cmdStream(response) {
    const reader = response.body.getReader();
    const decoder = new TextDecoder('utf-8');

    let result = '';
    let safety = 1000;
    while(true) {
        const { done, value } = await reader.read();
        
        if (done || safety-- <= 0) {
            break;
        }

        const text = decoder.decode(value, { stream: !done });
        result += text;
        let chunk = null;

        while(result && result.includes('\n')) {
            [chunk, result] = splitOnFirst(result, '\n');
            if(chunk) {
                const [cmd, data] = splitOnFirst(chunk, ':');
                yield {cmd: cmd.trim(), data: data.trim()};
            }
        }
    }

    return true;
}

export default {
    props: {
        exporting: {
            type: Boolean,
            default: false
        },
        files: {
            type: Array,
            default: () => []
        }
    }, 

    data() {
        return {
            newExport: null
        };
    },

    computed: {
        sortedFiles() {
            return this.files.sort((a, b) => new Date(b.date) - new Date(a.date));
        }
    },

    methods: {
        fetch(path, options) {
            options = Object.assign(options || {}, {
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    "x-requested-with": "xmlhttprequest",
                    "content-type": "application/json",
                    ...options.headers
                }
            });

            const config = this.$api.config;

            // adapt headers for all non-GET and non-POST methods
            if (
                config.methodOverwrite &&
                options.method !== "GET" &&
                options.method !== "POST"
            ) {
                options.headers["x-http-method-override"] = options.method;
                options.method = "POST";
            }

            // CMS specific options via callback
            options = config.onPrepare(options);

            // fetch the request's response
            return fetch(
                [config.endpoint, path].join(
                    config.endpoint.endsWith("/") || path.startsWith("/") ? "" : "/"
                ),
                options
            );
        },

        downloadFile(file) {
            const config = this.$api.config;
            const path = `/exports/${file.id}/download`;

            window.location.href = [config.endpoint, path].join(
                config.endpoint.endsWith("/") || path.startsWith("/") ? "" : "/"
            )
        },
        deleteFile(file) {
            this.$dialog(`export/${file.id}/delete`);
        },
        
        niceDate(datetimestring) {
            const date = new Date(datetimestring);
            return date.toLocaleString();
        },

        niceSize(size) {
            const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            let i = 0;
            while (size >= 1024) {
                size /= 1024;
                ++i;
            }
            return `${Math.round(size * 100) / 100} ${units[i]}`;
        },

        createExport() {
            this.$dialog('export/create', ({data, dialog}) => {
                if(data.basePath) {
                    this.startExport(data);
                    dialog.close();
                } else {
                    dialog.error('Please select a base path');
                }
            });
        },

        async startExport({ recreateImages = false, basePath = undefined }) {
            this.exporting = true;

            let count = Infinity;
            this.newExport = {
                id: null,
                date: null,
                status: null,
                size: null,
                basePath: basePath
            };

            for await(const {cmd, data} of cmdStream(await this.fetch('/exports', {method: 'POST', body: JSON.stringify({recreateImages, basePath})}))) {
                if(cmd === 'id') {
                    this.$set(this.newExport, 'id', data);
                } else if(cmd === 'date') {
                    this.$set(this.newExport, 'date', data);
                } else if(cmd === 'basepath') {
                    this.$set(this.newExport, 'basePath', data);
                } else if(cmd === 'count') {
                    count = parseInt(data);
                    this.$set(this.newExport, 'count', parseInt(data));
                } else if(cmd === 'progress') {
                    this.$set(this.newExport, 'status', parseInt(data) / count);
                } else if(cmd === 'size') {
                    //it's nicer to look at if we let the progress bar animation finish
                    setTimeout(() => {
                        this.files.push({
                            id: this.newExport.id,
                            date: this.newExport.date,
                            size: data,
                            basePath: this.newExport.basePath
                        });
                        this.newExport = null;
                        this.exporting = false;
                    }, 300);
                }
            }
        },
    },
}
</script>
<style>
.rs-exports {
  width: 100%;
  table-layout: fixed;
  border-spacing: 1px;
}
.rs-exports td,
.rs-exports th {
  text-align: left;
  font-size: var(--text-sm);
  padding: var(--spacing-2);
  white-space: nowrap;
  text-overflow: ellipsis;
  background: var(--color-white);
}
.rs-exports__options {
  width: 100px;
}
</style>