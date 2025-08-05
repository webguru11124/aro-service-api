import * as pulumi from "@pulumi/pulumi";
import * as aws from "@pulumi/aws";
import * as k8s from "@pulumi/kubernetes";

const config = new pulumi.Config();

type ResourceTags = Record<string, string>;
const tags: ResourceTags = {
    'aptive:gitlab-url': 'https://github.com/aptive-env/aro-service-api',
    'aptive:third-party': 'false',
    'aptive:configured-with': 'pulumi',
    'aptive:compliance': 'false',
    'aptive:region': 'us-east-1',
    'aptive:owner-team-id': 'aro'
}

const env = pulumi.getStack();

// get the k8s provider
const k8sStackReference = new pulumi.StackReference(`aptive/ops-eks/${config.require("eksStackName")}`);
const clusterName = config.require("eksStackName");
const k8sProvider = new k8s.Provider('k8s-provider', {});
const secretArn = k8sStackReference.getOutput('chartOutputs').apply(chartOutputs => chartOutputs.externalSecretsRoleArn);

const version = process.env.IMAGE_TAG;
if(!version) {
    throw new Error("env var IMAGE_TAG is required");
}

const imageRepo = "986611149894.dkr.ecr.us-east-1.amazonaws.com/aro:" + version;

/******** AWS *********/

// create an empty secret
const secret = new aws.secretsmanager.Secret('aro-secret-new', {
    name: `${env}-aro-secret-new`,
    tags:  {
        ...tags,
        [`eks-cluster/${clusterName}`]: 'owned'
    }
});

// create a secret version
const secretVersion = new aws.secretsmanager.SecretVersion('aro-secret-version', {
    secretId: secret.id,
    secretString: `{"DB_PASSWORD": ""}`
});

// create a service account IAM role
const iamRole = new aws.iam.Role('aro-iam-role', {
    name: `${env}-aro-sa-role`,
    assumeRolePolicy: pulumi.all([k8sStackReference.getOutput('clusterOidcProviderArn'), k8sStackReference.getOutput('clusterOidcProvider')]).apply(([arn, provider]) => JSON.stringify({
        Version: "2012-10-17",
        Statement: [{
            Effect: "Allow",
            Principal: {
                Federated: arn
            },
            Action: "sts:AssumeRoleWithWebIdentity",
            Condition: {
                StringEquals: {
                    [`${provider}:aud`]: "sts.amazonaws.com",
                },
            },
        }],
    })),
    tags: tags
});

// create sqs queue
const optimizationQueue = new aws.sqs.Queue(`${env}-aro-route-optimization`, {
    name: `${env}-aro-route-optimization`,
    tags: {
        ...tags,
        [`eks-cluster/${clusterName}`]: 'owned'
    },
});
const metricsQueue = new aws.sqs.Queue(`${env}-aro-metrics`, {
    name: `${env}-aro-metrics`,
    tags: {
        ...tags,
        [`eks-cluster/${clusterName}`]: 'owned'
    },
});
const statsQueue = new aws.sqs.Queue(`${env}-aro-stats`, {
    name: `${env}-aro-stats`,
    tags: {
        ...tags,
        [`eks-cluster/${clusterName}`]: 'owned'
    },
});
const reportsQueue = new aws.sqs.Queue(`${env}-aro-reports`, {
    name: `${env}-aro-reports`,
    tags: {
        ...tags,
        [`eks-cluster/${clusterName}`]: 'owned'
    },
});
const appointmentsQueue = new aws.sqs.Queue(`${env}-aro-appointments`, {
    name: `${env}-aro-appointments`,
    tags: {
        ...tags,
        [`eks-cluster/${clusterName}`]: 'owned'
    },
});
const notificationsQueue = new aws.sqs.Queue(`${env}-aro-notifications`, {
    name: `${env}-aro-notifications`,
    tags: {
        ...tags,
        [`eks-cluster/${clusterName}`]: 'owned'
    },
});
const cachingQueue = new aws.sqs.Queue(`${env}-aro-caching`, {
    name: `${env}-aro-caching`,
    tags: {
        ...tags,
        [`eks-cluster/${clusterName}`]: 'owned'
    },
});

const queues = [
    optimizationQueue,
    metricsQueue,
    statsQueue,
    reportsQueue,
    appointmentsQueue,
    notificationsQueue,
    cachingQueue
];

// create an IAM policy to allow writes to the queues
const sqsPolicy = new aws.iam.Policy('aro-sqs-policy', {
    policy: pulumi.all(queues.map(q => q.arn)).apply((arns) => JSON.stringify({
        Version: "2012-10-17",
        Statement: [{
            Effect: "Allow",
            Action: [
                "sqs:DeleteMessage",
                "sqs:GetQueueAttributes",
                "sqs:GetQueueUrl",
                "sqs:PurgeQueue",
                "sqs:ReceiveMessage",
                "sqs:SendMessage",
                "sqs:ChangeMessageVisibility",
            ],
            Resource: arns,
        },
        {
            Effect: "Allow",
            Action: [
                "dynamodb:Get*"
            ],
            Resource: config.require("officeCredentialsTableArn")
        }]
    })),
    tags: tags
});

// attach the policy to the role
const sqsPolicyAttachment = new aws.iam.RolePolicyAttachment('aro-sqs-policy-attachment', {
    policyArn: sqsPolicy.arn,
    role: iamRole.name,
});

/******** K8S *********/

// create a namespace
const namespace = new k8s.core.v1.Namespace('aro-namespace', {
    metadata: {
        name: 'aro',
        labels: {
            name: 'aro'
        }
    }
}, { provider: k8sProvider });

// create a service account
const serviceAccount = new k8s.core.v1.ServiceAccount('aro-service-account', {
    metadata: {
        name: 'aro-service-account',
        namespace: namespace.metadata.name,
        annotations: {
            "eks.amazonaws.com/role-arn": iamRole.arn
        }
    }
}, { provider: k8sProvider });

// Secrets part 1 - secretstore
const secretStore = new k8s.apiextensions.CustomResource('aro-secretstore', {
    apiVersion: 'external-secrets.io/v1beta1',
    kind: 'SecretStore',
    metadata: {
        name: 'aro-secretstore',
        namespace: namespace.metadata.name
    },
    spec: {
        provider: {
            aws: {
                region: 'us-east-1',
                service: "SecretsManager",
                role: secretArn
            }
        }
    }
}, { provider: k8sProvider });

// Secrets part 2 - external secret
const externalSecret = new k8s.apiextensions.CustomResource('aro-external-secret', {
    apiVersion: 'external-secrets.io/v1beta1',
    kind: 'ExternalSecret',
    metadata: {
        name: 'aro-external-secret',
        namespace: namespace.metadata.name
    },
    spec: {
        refreshInterval: "5m",
        secretStoreRef: {
            name: secretStore.metadata.name,
            kind: "SecretStore"
        },
        target: {
            name: "aro-secrets",
            creationPolicy: "Owner"
        },
        dataFrom: [{
            extract: {
                key: secret.name
            }
        }]
    },
}, { provider: k8sProvider });

const configMapName = "aro-configmap-" + Date.now();
const configMap = new k8s.kustomize.Directory("aro-config-map", {
    directory: `../k8s/overlays/${pulumi.getStack()}`,
    transformations: [
        (obj: any, opts: pulumi.CustomResourceOptions) => {
            if (obj.metadata.name.endsWith("aro-configmap")) {
                obj.metadata.name = configMapName;
                obj.data.SQS_ROUTE_OPTIMIZATION_QUEUE = optimizationQueue.name;
                obj.data.COLLECT_METRICS_QUEUE  = metricsQueue.name;
                obj.data.SERVICE_STATS_QUEUE = statsQueue.name;
                obj.data.BUILD_REPORTS_QUEUE = reportsQueue.name;
                obj.data.SCHEDULE_APPOINTMENTS_QUEUE = appointmentsQueue.name;
                obj.data.SEND_NOTIFICATIONS_QUEUE = notificationsQueue.name;
                obj.data.CACHING_QUEUE = cachingQueue.name;
            }
            return obj;
        }
    ]
}, { provider: k8sProvider });

// create a deployment
const deployment = new k8s.apps.v1.Deployment('aro-deployment', {
    metadata: {
        namespace: namespace.metadata.name,
        name: 'aro',
        labels: {
            app: 'aro',
            "tags.datadoghq.com/env": config.require("datadogEnv"),
            "tags.datadoghq.com/service": "aro",
            "tags.datadoghq.com/version": version,
            "admission.datadoghq.com/enabled": "true",
            "app.kubernetes.io/name": `aro`,
        },
        annotations: {
            "configmap.reloader.stakater.com/reload": configMapName,
            "secret.reloader.stakater.com/reload": "aro-secrets",
            "ad.datadoghq.com/aro.logs": `[{"source": "container","service":"aro","tags":["env":"${config.require("datadogEnv")}"]}]`,
        }
    },
    spec: {
        replicas: 2,
        revisionHistoryLimit: 1,
        selector: {
            matchLabels: {
                app: 'aro'
            }
        },
        template: {
            metadata: {
                labels: {
                    app: 'aro',
                    "tags.datadoghq.com/env": config.require("datadogEnv"),
                    "tags.datadoghq.com/service": "aro",
                    "tags.datadoghq.com/version": version,
                    "admission.datadoghq.com/enabled": "true",
                    "app.kubernetes.io/name": `aro`,
                }
            },
            spec: {
                serviceAccountName: serviceAccount.metadata.name,
                volumes: [{
                    name: 'nginx-config',
                    configMap: {
                        name: 'aro-nginx-config',
                    }
                }],
                containers: [{
                    name: 'aro',
                    image: `${imageRepo}`,
                    imagePullPolicy: 'Always',
                    envFrom: [{
                        configMapRef: {
                            name: configMapName
                        },
                    },{
                        secretRef: {
                            name: 'aro-secrets'
                        }
                    }],
                    ports: [{
                        name: 'http',
                        containerPort: 9000,
                        protocol: 'TCP'
                    }],
                    livenessProbe: {
                        tcpSocket: {
                            port: 'http'
                        },
                        initialDelaySeconds: 5,
                        periodSeconds: 10
                    },
                    readinessProbe: {
                        tcpSocket: {
                            port: 'http'
                        },
                        initialDelaySeconds: 5,
                        periodSeconds: 10
                    },
                    resources: {
                        limits: {
                            memory: "256Mi"
                        },
                        requests: {
                            cpu: "50m",
                            memory: "256Mi"
                        }
                    }
                }, {
                    name: 'nginx-sidecar',
                    image: 'nginx:1.25.5-alpine',
                    imagePullPolicy: 'IfNotPresent',
                    ports: [{
                        containerPort: 80,
                        protocol: 'TCP'
                    }],
                    volumeMounts: [{
                        name: 'nginx-config',
                        mountPath: '/etc/nginx/nginx.conf',
                        subPath: 'nginx.conf',
                    }]
                }],
                nodeSelector: {},
                affinity: {
                    nodeAffinity: {
                        preferredDuringSchedulingIgnoredDuringExecution: [
                            {
                                weight: 100,
                                preference: {
                                    matchExpressions: [
                                        {
                                            key: "node.kubernetes.io/distribution",
                                            operator: "In",
                                            values: [
                                                "spot"
                                            ]
                                        }
                                    ]
                                }
                            }
                        ]
                    }
                },
                tolerations: [],
                topologySpreadConstraints: [
                    {
                        maxSkew: 1,
                        topologyKey: "topoplogy.kubernetes.io/zone",
                        whenUnsatisfiable: "ScheduleAnyway",
                        labelSelector: {
                            matchLabels: {
                                "app.kubernetes.io/name": `aro`,
                            }
                        }
                    },
                ]
            }
        }
    }
}, { provider: k8sProvider });

// create a service
const service = new k8s.core.v1.Service('aro-service', {
    metadata: {
        namespace: namespace.metadata.name,
        name: 'aro',
        labels: {
            app: 'aro'
        }
    },
    spec: {
        selector: {
            app: 'aro'
        },
        ports: [{
            port: 80,
            targetPort: 80
        }]
    }
}, { provider: k8sProvider });

// pod autoscaler
const hpa = new k8s.autoscaling.v2.HorizontalPodAutoscaler('aro-hpa', {
    metadata: {
        namespace: namespace.metadata.name,
        name: 'aro',
        labels: {
            app: 'aro'
        }
    },
    spec: {
        scaleTargetRef: {
            apiVersion: "apps/v1",
            kind: "Deployment",
            name: deployment.metadata.name
        },
        minReplicas: 2,
        maxReplicas: 100,
        metrics: [
            {
                type: "Resource",
                resource: {
                    name: "cpu",
                    target: {
                        type: "Utilization",
                        averageUtilization: 80
                    }
                }
            },
            {
                type: "Resource",
                resource: {
                    name: "memory",
                    target: {
                        type: "Utilization",
                        averageUtilization: 80
                    }
                }
            }
        ]
    }
}, { provider: k8sProvider });

// disruption budget
const disruptionBudget = new k8s.policy.v1.PodDisruptionBudget('aro-pdb', {
    metadata: {
        namespace: namespace.metadata.name,
        name: 'aro',
        labels: {
            app: 'aro'
        }
    },
    spec: {
        minAvailable: "50%",
        selector: {
            matchLabels: {
                app: 'aro'
            }
        }
    }
}, { provider: k8sProvider });

//  rewrite the API path so the aro app can understand it
const apiPathMiddleware = new k8s.apiextensions.CustomResource("aro-api-path-middleware", {
    apiVersion: "traefik.io/v1alpha1",
    kind: "Middleware",
    metadata: {
        name: "aro-api-url-rewrite",
        namespace: namespace.metadata.name
    },
    spec: {
        replacePathRegex: {
            regex: "^/aro/(.*)",
            replacement: "/api/$1"
        }
    }
}, { provider: k8sProvider });

// create a jwt auth middleware
const jwtAuthMiddleware = new k8s.apiextensions.CustomResource("aro-jwt-auth-middleware", {
    apiVersion: "traefik.io/v1alpha1",
    kind: "Middleware",
    metadata: {
        name: "aro-jwt-auth-middleware",
        namespace: namespace.metadata.name
    },
    spec: {
        plugin: {
            jwtAuth: {
                source: "crmFusionAuthJwt",
                forwardHeaders: {
                    "Expires-At": "exp",
                    'Aptive-Api-Account-Id': "sub"
                },
                claims: "Equals(`scope`, `" + config.require("apiAuthScope") + "`)"
            }
        }
    }
}, { provider: k8sProvider });

// create an ingress route
const ingressRoute = new k8s.apiextensions.CustomResource("aro-ingress-route", {
    apiVersion: "traefik.io/v1alpha1",
    kind: "IngressRoute",
    metadata: {
        name: "aro-ingress-route",
        annotations: {
            "kubernetes.io/ingress.class": "traefik",
        },
    },
    spec: {
        entryPoints: ["web", "websecure"],
        routes: [
        {
            match: "Host(`" + config.require('apiHostname') + "`) && PathPrefix(`/aro`)",
            kind: "Rule",
            services: [{
                name: "aro",
                namespace: namespace.metadata.name,
                port: 80
            }],
            middlewares: [{
                name: "aro-jwt-auth-middleware",
                namespace: namespace.metadata.name
            },{
                name: "aro-api-url-rewrite",
                namespace: namespace.metadata.name
            },{
                name: "remove-cf-headers",
                namespace: "traefikee"
            }]
        }
        ],
        tls: {
            certResolver: "letsencrypt"
        }
    },
}, { provider: k8sProvider });


// create a service account to allow scheduled restart of queue consumers
// This will solve php long-running processes memory leaks
const queueConsumerServiceAccount = new k8s.core.v1.ServiceAccount('aro-qc-service-account', {
    metadata: {
        name: 'aro-qc-service-account',
        namespace: namespace.metadata.name
    }
}, { provider: k8sProvider });

// create a role to allow the service account to restart deployments
const queueConsumerRole = new k8s.rbac.v1.Role('aro-qc-role', {
    metadata: {
        name: 'aro-qc-role',
        namespace: namespace.metadata.name
    },
    rules: [{
        apiGroups: ["apps", "extensions"],
        resources: ["deployments"],
        verbs: ["get", "list", "patch", "watch"]
    }]
}, { provider: k8sProvider });

// create a role binding to attach the role to the service account
const queueConsumerRoleBinding = new k8s.rbac.v1.RoleBinding('aro-qc-role-binding', {
    metadata: {
        name: 'aro-qc-role-binding',
        namespace: namespace.metadata.name
    },
    roleRef: {
        apiGroup: "rbac.authorization.k8s.io",
        kind: "Role",
        name: queueConsumerRole.metadata.name
    },
    subjects: [{
        kind: "ServiceAccount",
        name: queueConsumerServiceAccount.metadata.name
    }]
}, { provider: k8sProvider });

// add deployments for queue consumers
const queueConsumers = [
    {
        name: "route-optimization",
        command: "php artisan queue:work sqs --queue=${SQS_ROUTE_OPTIMIZATION_QUEUE} --timeout=3600",
        replicaCount: 1,
        restartSchedule: "30 23 * * *"
    },
    {
        name: "collect-metrics",
        command: "php artisan queue:work sqs --queue=${COLLECT_METRICS_QUEUE}",
        replicaCount: 1,
        restartSchedule: "30 23 * * *"
    },
    {
        name: "stats",
        command: "php artisan queue:work sqs --queue=${SERVICE_STATS_QUEUE}",
        replicaCount: 1,
        restartSchedule: "30 23 * * *"
    },
    {
        name: "build-reports",
        command: "php artisan queue:work sqs --queue=${BUILD_REPORTS_QUEUE}",
        replicaCount: 1,
        restartSchedule: "30 23 * * *"
    },
    {
        name: "schedule-appointments",
        command: "php artisan queue:work sqs --queue=${SCHEDULE_APPOINTMENTS_QUEUE}",
        replicaCount: 1,
        restartSchedule: "30 23 * * *"
    },
    {
        name: "send-notifications",
        command: "php artisan queue:work sqs --queue=${SEND_NOTIFICATIONS_QUEUE}",
        replicaCount: 1,
        restartSchedule: "30 23 * * *"
    },
    {
        name: "caching",
        command: "php artisan queue:work sqs --queue=${CACHING_QUEUE}",
        replicaCount: 1,
        restartSchedule: "30 23 * * *"
    }
];


queueConsumers.forEach((consumer, index) => {
    const qcDeployment = new k8s.apps.v1.Deployment(`aro-qc-${consumer.name}`, {
        metadata: {
            namespace: namespace.metadata.name,
            name: `aro-qc-${consumer.name}`,
            labels: {
                app: `aro-qc-${consumer.name}`,
                "tags.datadoghq.com/env": config.require("datadogEnv"),
                "tags.datadoghq.com/service": `aro-qc-${consumer.name}`,
                "tags.datadoghq.com/version": version,
                "admission.datadoghq.com/enabled": "true",
                "app.kubernetes.io/name": `aro`,
            },
            annotations: {
                "configmap.reloader.stakater.com/reload": configMapName,
                "secret.reloader.stakater.com/reload": "aro-secrets",
                [`ad.datadoghq.com/aro-qc-${consumer.name}.logs`]: `[{"source": "container","service":"aro-qc-${consumer.name}","tags":["env":"${config.require("datadogEnv")}"]}]`,
            }
        },
        spec: {
            replicas: consumer.replicaCount,
            revisionHistoryLimit: 1,
            selector: {
                matchLabels: {
                    app: `aro-qc-${consumer.name}`
                }
            },
            template: {
                metadata: {
                    labels: {
                        app: `aro-qc-${consumer.name}`,
                        "tags.datadoghq.com/env": config.require("datadogEnv"),
                        "tags.datadoghq.com/service": `aro-qc-${consumer.name}`,
                        "tags.datadoghq.com/version": version,
                        "admission.datadoghq.com/enabled": "true",
                        "app.kubernetes.io/name": `aro`,
                    },
                    annotations: {
                        [`ad.datadoghq.com/aro-qc-${consumer.name}.logs`]: `[{"source": "container","service":"aro-qc-${consumer.name}","tags":["env":"${config.require("datadogEnv")}"]}]`,
                    }
                },
                spec: {
                    serviceAccountName: serviceAccount.metadata.name,
                    containers: [{
                        name: `aro-qc-${consumer.name}`,
                        image: `${imageRepo}`,
                        imagePullPolicy: 'Always',
                        command: ['/bin/sh', '-c'],
                        args: [consumer.command],
                        envFrom: [{
                            configMapRef: {
                                name: configMapName
                            },
                        },{
                            secretRef: {
                                name: 'aro-secrets'
                            }
                        }],
                        resources: {
                            limits: {
                                memory: "256Mi"
                            },
                            requests: {
                                cpu: "100m",
                                memory: "256Mi"
                            }
                        },
                    }],
                    nodeSelector: {},
                    affinity: {
                        nodeAffinity: {
                            preferredDuringSchedulingIgnoredDuringExecution: [
                                {
                                    weight: 100,
                                    preference: {
                                        matchExpressions: [
                                            {
                                                key: "node.kubernetes.io/distribution",
                                                operator: "In",
                                                values: [
                                                    "spot"
                                                ]
                                            }
                                        ]
                                    }
                                }
                            ]
                        }
                    },
                    tolerations: [],
                    topologySpreadConstraints: [
                        {
                            maxSkew: 1,
                            topologyKey: "topoplogy.kubernetes.io/zone",
                            whenUnsatisfiable: "ScheduleAnyway",
                            labelSelector: {
                                matchLabels: {
                                    "app.kubernetes.io/name": `aro`,
                                }
                            }
                        }
                    ]
                }
            }
        }
    }, { provider: k8sProvider });

    // cron to restart the deployment daily
    const cronJob = new k8s.batch.v1.CronJob(`aro-qc-restart-${consumer.name}`, {
        metadata: {
            namespace: namespace.metadata.name,
            name: `aro-qc-restart-${consumer.name}`,
            labels: {
                app: `aro-qc-${consumer.name}`
            }
        },
        spec: {
            successfulJobsHistoryLimit: 1,
            failedJobsHistoryLimit: 2,
            concurrencyPolicy: "Forbid",
            schedule: consumer.restartSchedule,
            jobTemplate: {
                spec: {
                    backoffLimit: 2,
                    activeDeadlineSeconds: 600,
                    template: {
                        spec: {
                            serviceAccountName: queueConsumerServiceAccount.metadata.name,
                            restartPolicy: "Never",
                            containers: [{
                                name: "kubectl",
                                image: "bitnami/kubectl:latest",
                                command: ["kubectl" , "rollout", "restart", "deployment", `aro-qc-${consumer.name}`],
                            }],
                        }
                    }
                }
            }
        }
    }, { provider: k8sProvider });
});

