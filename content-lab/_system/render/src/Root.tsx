import React from 'react';
import {Composition, Still} from 'remotion';
import {VatanReel, ReelProps, reelDuration} from './VatanReel';
import {Story, StoryProps, Cover, CoverProps, Slide, SlideProps, Poster, PosterProps} from './Stills';

export const Root: React.FC = () => <>
  <Composition id="VatanReel" component={VatanReel as any} width={1080} height={1920} fps={30} durationInFrames={300}
    defaultProps={{theme: 'punch', scenes: [{type: 'text', text: 'تست', dur: 60}]} as ReelProps} calculateMetadata={({props}) => ({durationInFrames: Math.max(1, reelDuration(props as ReelProps))})} />
  <Composition id="Story" component={Story as any} width={1080} height={1920} fps={30} durationInFrames={150} defaultProps={{kind: 'teaser', title: 'تست'} as StoryProps} />
  <Still id="Cover" component={Cover as any} width={1080} height={1920} defaultProps={{title: 'تست'} as CoverProps} />
  <Still id="Slide" component={Slide as any} width={1080} height={1350} defaultProps={{kind: 'tip', title: 'تست', n: 2, total: 5} as SlideProps} />
  <Still id="Poster" component={Poster as any} width={1080} height={1350} defaultProps={{title: 'تست'} as PosterProps} />
</>;
